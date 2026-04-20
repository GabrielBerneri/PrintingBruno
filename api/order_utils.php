<?php
/**
 * Order and stock reservation helpers.
 */

require_once __DIR__ . '/product_variants.php';

const PB_RESERVATION_ACTIVE = 'active';
const PB_RESERVATION_CONSUMED = 'consumed';
const PB_RESERVATION_RELEASED = 'released';
const PB_RESERVATION_EXPIRED = 'expired';
const PB_RESERVATION_RESTORED = 'restored';
const PB_RESERVATION_TTL_MINUTES = 30;
const PB_PAYMENT_STATUSES = ['pending', 'under_review', 'approved', 'rejected', 'cancelled', 'refunded', 'charged_back'];
const PB_FULFILLMENT_STATUSES = ['queued', 'in_production', 'ready', 'shipped', 'delivered', 'cancelled'];
const PB_PAYMENT_TRANSITIONS = [
    'pending' => ['pending', 'under_review', 'approved', 'rejected', 'cancelled'],
    'under_review' => ['under_review', 'pending', 'approved', 'rejected', 'cancelled'],
    'approved' => ['approved', 'refunded', 'charged_back'],
    'rejected' => ['rejected'],
    'cancelled' => ['cancelled'],
    'refunded' => ['refunded'],
    'charged_back' => ['charged_back'],
];
const PB_FULFILLMENT_TRANSITIONS = [
    'queued' => ['queued', 'in_production', 'ready', 'cancelled'],
    'in_production' => ['in_production', 'ready', 'cancelled'],
    'ready' => ['ready', 'shipped', 'delivered', 'cancelled'],
    'shipped' => ['shipped', 'delivered'],
    'delivered' => ['delivered'],
    'cancelled' => ['cancelled', 'queued'],
];

function pbNowSql(): string {
    return date('Y-m-d H:i:s');
}

function pbReservationExpirySql(): string {
    return date('Y-m-d H:i:s', time() + (PB_RESERVATION_TTL_MINUTES * 60));
}

function pbExpireReservations(PDO $db): int {
    $stmt = $db->prepare("
        UPDATE stock_reservations sr
        INNER JOIN orders o ON o.id = sr.order_id
        SET sr.status = ?, sr.updated_at = CURRENT_TIMESTAMP
        WHERE sr.status = ?
          AND sr.expires_at <= NOW()
          AND COALESCE(o.payment_status, 'pending') IN ('pending', 'under_review')
    ");
    $stmt->execute([PB_RESERVATION_EXPIRED, PB_RESERVATION_ACTIVE]);
    return $stmt->rowCount();
}

function pbNormalizePaymentStatus(?string $status): string {
    $status = strtolower(trim((string)$status));
    return in_array($status, PB_PAYMENT_STATUSES, true) ? $status : 'pending';
}

function pbNormalizeFulfillmentStatus(?string $status): string {
    $status = strtolower(trim((string)$status));
    return in_array($status, PB_FULFILLMENT_STATUSES, true) ? $status : 'queued';
}

function pbMapMercadoPagoStatusToPaymentStatus(?string $status): string {
    $status = strtolower(trim((string)$status));

    return match ($status) {
        'approved'              => 'approved',
        'pending'               => 'pending',
        'in_process',
        'authorized'            => 'under_review',
        'rejected',
        'failure', 'failed'     => 'rejected',
        'cancelled'             => 'cancelled',
        'refunded'              => 'refunded',
        'charged_back'          => 'charged_back',
        default                 => 'pending',
    };
}

function pbMapLegacyStatusToLifecycle(?string $status, ?string $checkoutStatus = null): array {
    $status = strtolower(trim((string)$status));
    $checkoutStatus = strtolower(trim((string)$checkoutStatus));

    return match ($status) {
        'approved' => [
            'payment_status' => 'approved',
            'fulfillment_status' => 'queued',
        ],
        'in_process' => [
            'payment_status' => $checkoutStatus === 'completed' ? 'approved' : 'under_review',
            'fulfillment_status' => $checkoutStatus === 'completed' ? 'in_production' : 'queued',
        ],
        'shipped' => [
            'payment_status' => 'approved',
            'fulfillment_status' => 'shipped',
        ],
        'delivered' => [
            'payment_status' => 'approved',
            'fulfillment_status' => 'delivered',
        ],
        'rejected' => [
            'payment_status' => 'rejected',
            'fulfillment_status' => 'cancelled',
        ],
        'cancelled' => [
            'payment_status' => 'cancelled',
            'fulfillment_status' => 'cancelled',
        ],
        'refunded' => [
            'payment_status' => 'refunded',
            'fulfillment_status' => 'cancelled',
        ],
        'charged_back' => [
            'payment_status' => 'charged_back',
            'fulfillment_status' => 'cancelled',
        ],
        default => [
            'payment_status' => 'pending',
            'fulfillment_status' => 'queued',
        ],
    };
}

function pbDeriveLegacyOrderStatus(string $paymentStatus, string $fulfillmentStatus): string {
    $paymentStatus = pbNormalizePaymentStatus($paymentStatus);
    $fulfillmentStatus = pbNormalizeFulfillmentStatus($fulfillmentStatus);

    if (in_array($paymentStatus, ['rejected', 'cancelled', 'refunded', 'charged_back'], true)) {
        return $paymentStatus;
    }

    if ($paymentStatus === 'under_review') {
        return 'in_process';
    }

    if ($paymentStatus !== 'approved') {
        return 'pending';
    }

    return match ($fulfillmentStatus) {
        'shipped' => 'shipped',
        'delivered' => 'delivered',
        default => 'approved',
    };
}

function pbGetOrderLifecycle(array $order): array {
    $fallback = pbMapLegacyStatusToLifecycle($order['status'] ?? 'pending', $order['checkout_status'] ?? 'pending');

    $paymentStatus = array_key_exists('payment_status', $order) && $order['payment_status'] !== null && $order['payment_status'] !== ''
        ? pbNormalizePaymentStatus($order['payment_status'])
        : $fallback['payment_status'];

    $fulfillmentStatus = array_key_exists('fulfillment_status', $order) && $order['fulfillment_status'] !== null && $order['fulfillment_status'] !== ''
        ? pbNormalizeFulfillmentStatus($order['fulfillment_status'])
        : $fallback['fulfillment_status'];

    return [
        'payment_status' => $paymentStatus,
        'fulfillment_status' => $fulfillmentStatus,
        'status' => pbDeriveLegacyOrderStatus($paymentStatus, $fulfillmentStatus),
    ];
}

function pbDeriveCheckoutStatus(array $order, string $paymentStatus): string {
    $paymentStatus = pbNormalizePaymentStatus($paymentStatus);
    if (in_array($paymentStatus, ['approved', 'refunded', 'charged_back'], true)) {
        return 'completed';
    }

    if (in_array($paymentStatus, ['rejected', 'cancelled'], true)) {
        return 'failed';
    }

    $current = strtolower(trim((string)($order['checkout_status'] ?? 'pending')));
    if ($current === 'completed') {
        return 'completed';
    }

    $paymentMethod = strtolower(trim((string)($order['payment_method'] ?? 'mercadopago')));
    $preferenceStatus = strtolower(trim((string)($order['mp_preference_status'] ?? 'pending')));
    if ($paymentMethod !== 'mercadopago' || in_array($preferenceStatus, ['created', 'not_required', 'failed'], true) || $current === 'ready') {
        return 'ready';
    }

    return 'pending';
}

function pbResolveOrderLifecycle(array $order, array $changes): array {
    $current = pbGetOrderLifecycle($order);
    $paymentStatus = $current['payment_status'];
    $fulfillmentStatus = $current['fulfillment_status'];

    if (array_key_exists('status', $changes) && !array_key_exists('payment_status', $changes) && !array_key_exists('fulfillment_status', $changes)) {
        $mapped = pbMapLegacyStatusToLifecycle($changes['status'] ?? 'pending', $order['checkout_status'] ?? 'pending');
        $paymentStatus = $mapped['payment_status'];
        $fulfillmentStatus = $mapped['fulfillment_status'];
    }

    if (array_key_exists('payment_status', $changes)) {
        $paymentStatus = pbNormalizePaymentStatus($changes['payment_status']);
    }

    if (array_key_exists('fulfillment_status', $changes)) {
        $fulfillmentStatus = pbNormalizeFulfillmentStatus($changes['fulfillment_status']);
    } elseif (in_array($paymentStatus, ['rejected', 'cancelled', 'refunded', 'charged_back'], true) && !in_array($fulfillmentStatus, ['shipped', 'delivered'], true)) {
        $fulfillmentStatus = 'cancelled';
    } elseif ($paymentStatus === 'approved' && $current['payment_status'] !== 'approved' && $fulfillmentStatus === 'cancelled') {
        $fulfillmentStatus = 'queued';
    }

    return [
        'payment_status' => $paymentStatus,
        'fulfillment_status' => $fulfillmentStatus,
        'status' => pbDeriveLegacyOrderStatus($paymentStatus, $fulfillmentStatus),
        'checkout_status' => pbDeriveCheckoutStatus(array_merge($order, [
            'payment_status' => $paymentStatus,
            'fulfillment_status' => $fulfillmentStatus,
        ]), $paymentStatus),
    ];
}

function pbApplyLifecycleTransitionEffects(PDO $db, array $currentOrder, array $nextLifecycle): void {
    $oldPaymentStatus = pbGetOrderLifecycle($currentOrder)['payment_status'];
    $newPaymentStatus = pbNormalizePaymentStatus($nextLifecycle['payment_status'] ?? 'pending');

    if ($oldPaymentStatus !== 'approved' && $newPaymentStatus === 'approved') {
        pbConsumeReservationsForOrder($db, (int)$currentOrder['id']);
    }

    if ($oldPaymentStatus === 'approved' && in_array($newPaymentStatus, ['rejected', 'cancelled', 'refunded', 'charged_back'], true)) {
        pbRestoreConsumedReservationsForOrder($db, (int)$currentOrder['id']);
    }

    if ($oldPaymentStatus !== 'approved' && in_array($newPaymentStatus, ['rejected', 'cancelled'], true)) {
        pbReleaseReservationsForOrder($db, (int)$currentOrder['id']);
    }
}

function pbLifecycleTransitionError(array $currentOrder, array $nextLifecycle): ?string {
    $current = pbGetOrderLifecycle($currentOrder);
    $currentPayment = $current['payment_status'];
    $currentFulfillment = $current['fulfillment_status'];
    $nextPayment = pbNormalizePaymentStatus($nextLifecycle['payment_status'] ?? $currentPayment);
    $nextFulfillment = pbNormalizeFulfillmentStatus($nextLifecycle['fulfillment_status'] ?? $currentFulfillment);

    $allowedPayments = PB_PAYMENT_TRANSITIONS[$currentPayment] ?? [$currentPayment];
    if (!in_array($nextPayment, $allowedPayments, true)) {
        return 'La transición de cobro solicitada no es válida para el estado actual.';
    }

    $allowedFulfillment = PB_FULFILLMENT_TRANSITIONS[$currentFulfillment] ?? [$currentFulfillment];
    if (!in_array($nextFulfillment, $allowedFulfillment, true)) {
        return 'La transición operativa solicitada no es válida para el estado actual.';
    }

    if (in_array($nextFulfillment, ['shipped', 'delivered'], true) && $nextPayment !== 'approved') {
        return 'No podés marcar envío o entrega sin cobro aprobado.';
    }

    if ($nextPayment !== 'approved' && !in_array($nextFulfillment, ['queued', 'cancelled'], true)) {
        return 'Solo podés avanzar producción con cobro aprobado.';
    }

    if ($currentFulfillment === 'cancelled' && $nextFulfillment === 'queued' && $nextPayment !== 'approved') {
        return 'Solo podés reabrir una orden cancelada si el cobro queda aprobado.';
    }

    return null;
}

function pbGetReservedQuantities(PDO $db, array $productIds, ?int $excludeOrderId = null): array {
    $productIds = array_values(array_unique(array_map('intval', $productIds)));
    if (empty($productIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $sql = "
        SELECT product_id, COALESCE(SUM(quantity), 0) AS reserved_qty
        FROM stock_reservations
        WHERE status = ?
          AND product_id IN ($placeholders)
    ";
    $params = array_merge([PB_RESERVATION_ACTIVE], $productIds);
    if ($excludeOrderId !== null) {
        $sql .= " AND order_id != ?";
        $params[] = $excludeOrderId;
    }
    $sql .= " GROUP BY product_id";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $reserved = [];
    foreach ($stmt->fetchAll() as $row) {
        $reserved[(int)$row['product_id']] = (int)$row['reserved_qty'];
    }
    return $reserved;
}

function pbCreateReservations(PDO $db, int $orderId, array $orderItems): void {
    $expiresAt = pbReservationExpirySql();
    $hasVariantColumn = pbHasColumn($db, 'stock_reservations', 'variant_id');
    $stmt = $hasVariantColumn
        ? $db->prepare("
            INSERT INTO stock_reservations (order_id, product_id, variant_id, quantity, status, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ")
        : $db->prepare("
            INSERT INTO stock_reservations (order_id, product_id, quantity, status, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");

    foreach ($orderItems as $item) {
        if ($hasVariantColumn) {
            $variantId = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
            $stmt->execute([
                $orderId,
                (int)$item['product_id'],
                $variantId,
                (int)$item['quantity'],
                PB_RESERVATION_ACTIVE,
                $expiresAt,
            ]);
            continue;
        }

        $stmt->execute([
            $orderId,
            (int)$item['product_id'],
            (int)$item['quantity'],
            PB_RESERVATION_ACTIVE,
            $expiresAt,
        ]);
    }
}

function pbConsumeReservationsForOrder(PDO $db, int $orderId): void {
    $hasVariantColumn = pbHasColumn($db, 'stock_reservations', 'variant_id');
    $sql = $hasVariantColumn
        ? "
            SELECT id, product_id, variant_id, quantity
            FROM stock_reservations
            WHERE order_id = ? AND status = ?
            FOR UPDATE
        "
        : "
            SELECT id, product_id, quantity
            FROM stock_reservations
            WHERE order_id = ? AND status = ?
            FOR UPDATE
        ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$orderId, PB_RESERVATION_ACTIVE]);
    $reservations = $stmt->fetchAll();

    if (empty($reservations)) {
        return;
    }

    $consumeReservation = $db->prepare("UPDATE stock_reservations SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $updateProductStock = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
    $updateVariantStock = pbHasProductVariantsTable($db)
        ? $db->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?")
        : null;
    $affectedProductIds = [];

    foreach ($reservations as $reservation) {
        $qty = (int)$reservation['quantity'];
        $productId = (int)$reservation['product_id'];
        $variantId = $hasVariantColumn && !empty($reservation['variant_id']) ? (int)$reservation['variant_id'] : null;

        if ($variantId !== null && $updateVariantStock) {
            $updateVariantStock->execute([$qty, $variantId, $qty]);
            if ($updateVariantStock->rowCount() === 0) {
                throw new RuntimeException("No se pudo descontar stock para la variante {$variantId}");
            }
            $affectedProductIds[$productId] = $productId;
        } else {
            $updateProductStock->execute([$qty, $productId, $qty]);
            if ($updateProductStock->rowCount() === 0) {
                throw new RuntimeException("No se pudo descontar stock para el producto {$productId}");
            }
        }

        $consumeReservation->execute([PB_RESERVATION_CONSUMED, (int)$reservation['id']]);
    }

    foreach ($affectedProductIds as $productId) {
        pbSyncProductStock($db, (int)$productId);
    }
}

function pbReleaseReservationsForOrder(PDO $db, int $orderId, string $targetStatus = PB_RESERVATION_RELEASED): void {
    $stmt = $db->prepare("
        UPDATE stock_reservations
        SET status = ?, updated_at = CURRENT_TIMESTAMP
        WHERE order_id = ? AND status = ?
    ");
    $stmt->execute([$targetStatus, $orderId, PB_RESERVATION_ACTIVE]);
}

function pbRestoreConsumedReservationsForOrder(PDO $db, int $orderId): void {
    $hasVariantColumn = pbHasColumn($db, 'stock_reservations', 'variant_id');
    $sql = $hasVariantColumn
        ? "
            SELECT id, product_id, variant_id, quantity
            FROM stock_reservations
            WHERE order_id = ? AND status = ?
            FOR UPDATE
        "
        : "
            SELECT id, product_id, quantity
            FROM stock_reservations
            WHERE order_id = ? AND status = ?
            FOR UPDATE
        ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$orderId, PB_RESERVATION_CONSUMED]);
    $reservations = $stmt->fetchAll();

    if (empty($reservations)) {
        return;
    }

    $restoreReservation = $db->prepare("UPDATE stock_reservations SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $restoreProductStock = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
    $restoreVariantStock = pbHasProductVariantsTable($db)
        ? $db->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = ?")
        : null;
    $affectedProductIds = [];

    foreach ($reservations as $reservation) {
        $qty = (int)$reservation['quantity'];
        $productId = (int)$reservation['product_id'];
        $variantId = $hasVariantColumn && !empty($reservation['variant_id']) ? (int)$reservation['variant_id'] : null;

        if ($variantId !== null && $restoreVariantStock) {
            $restoreVariantStock->execute([$qty, $variantId]);
            $affectedProductIds[$productId] = $productId;
        } else {
            $restoreProductStock->execute([$qty, $productId]);
        }

        $restoreReservation->execute([PB_RESERVATION_RESTORED, (int)$reservation['id']]);
    }

    foreach ($affectedProductIds as $productId) {
        pbSyncProductStock($db, (int)$productId);
    }
}

function pbBuildCheckoutIdempotencyKey(array $body): string {
    $items = [];
    foreach (($body['items'] ?? []) as $item) {
        $productId = (int)($item['product_id'] ?? $item['id'] ?? 0);
        $variantId = (int)($item['variant_id'] ?? 0);
        $items[] = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => (int)($item['quantity'] ?? 0),
        ];
    }
    usort($items, static function ($a, $b) {
        return [$a['product_id'], $a['variant_id'], $a['quantity']] <=> [$b['product_id'], $b['variant_id'], $b['quantity']];
    });

    $payload = [
        'token' => trim((string)($body['idempotency_key'] ?? '')),
        'items' => $items,
        'customer' => [
            'email' => strtolower(trim((string)($body['customer']['email'] ?? ''))),
            'name' => trim((string)($body['customer']['name'] ?? '')),
            'phone' => trim((string)($body['customer']['phone'] ?? '')),
        ],
        'payment_method' => trim((string)($body['payment_method'] ?? 'mercadopago')),
    ];

    return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function pbFindOrderByIdempotencyKey(PDO $db, string $idempotencyKey): ?array {
    $stmt = $db->prepare("SELECT * FROM orders WHERE idempotency_key = ? LIMIT 1");
    $stmt->execute([$idempotencyKey]);
    $order = $stmt->fetch();
    return $order ?: null;
}

function pbBuildOrderAccessToken(int $orderId, string $orderNumber): string {
    $secret = defined('ORDER_ACCESS_SECRET') ? (string)ORDER_ACCESS_SECRET : '';
    if ($secret === '' || $orderId <= 0 || $orderNumber === '') {
        return '';
    }

    return hash_hmac('sha256', $orderId . '|' . $orderNumber, $secret);
}

function pbVerifyOrderAccessToken(int $orderId, string $orderNumber, string $providedToken): bool {
    $providedToken = trim($providedToken);
    if ($providedToken === '') {
        return false;
    }

    $expectedToken = pbBuildOrderAccessToken($orderId, $orderNumber);
    if ($expectedToken === '') {
        return false;
    }

    return hash_equals($expectedToken, $providedToken);
}

function pbBuildOrderReturnQuery(int $orderId, string $orderNumber): string {
    $params = [
        'order' => $orderId,
    ];

    $token = pbBuildOrderAccessToken($orderId, $orderNumber);
    if ($token !== '') {
        $params['ot'] = $token;
    }

    return http_build_query($params);
}
