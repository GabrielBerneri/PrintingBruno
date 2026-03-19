<?php
/**
 * PrintingBruno - Admin API: Orders Operations
 * GET /api/admin/orders.php          -> Paginated operational inbox
 * GET /api/admin/orders.php?id=X     -> Order detail
 * PUT /api/admin/orders.php?id=X     -> Update lifecycle and manual payment fields
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../order_utils.php';
require_once __DIR__ . '/../order_shipping.php';
require_once __DIR__ . '/audit.php';

if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

adminRequireCsrf();

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    pbExpireReservations($db);

    switch ($method) {
        case 'GET':
            if (!empty($_GET['id'])) {
                jsonResponse(getAdminOrderDetail($db, (int)$_GET['id']));
            }

            jsonResponse(getAdminOrdersList($db));
            break;

        case 'PUT':
            if (empty($_GET['id'])) {
                jsonResponse(['error' => 'Order ID required'], 400);
            }

            $orderId = (int)$_GET['id'];
            $body = getJsonBody();
            jsonResponse(updateAdminOrder($db, $orderId, $body));
            break;

        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log('admin/orders error: ' . $e->getMessage());
    jsonResponse(['error' => 'Server error'], 500);
}

function getAdminOrdersList(PDO $db): array {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = (int)($_GET['limit'] ?? 15);
    $limit = max(5, min(100, $limit));
    $offset = ($page - 1) * $limit;

    $q = trim((string)($_GET['q'] ?? ''));
    $paymentStatus = normalizeOptionalEnum($_GET['payment_status'] ?? null, PB_PAYMENT_STATUSES);
    $fulfillmentStatus = normalizeOptionalEnum($_GET['fulfillment_status'] ?? null, PB_FULFILLMENT_STATUSES);
    $paymentMethod = normalizeOptionalEnum($_GET['payment_method'] ?? null, ['mercadopago', 'transferencia', 'efectivo']);
    $queue = normalizeOptionalEnum($_GET['queue'] ?? null, ['requires_action', 'manual_review', 'production', 'ready', 'delivery', 'completed']);
    $dateFrom = normalizeDateInput($_GET['date_from'] ?? null);
    $dateTo = normalizeDateInput($_GET['date_to'] ?? null);

    $where = [];
    $params = [];

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ? OR o.customer_phone LIKE ?)";
        array_push($params, $like, $like, $like, $like);
    }

    if ($paymentStatus !== null) {
        $where[] = "COALESCE(o.payment_status, 'pending') = ?";
        $params[] = $paymentStatus;
    }

    if ($fulfillmentStatus !== null) {
        $where[] = "COALESCE(o.fulfillment_status, 'queued') = ?";
        $params[] = $fulfillmentStatus;
    }

    if ($paymentMethod !== null) {
        $where[] = "COALESCE(o.payment_method, 'mercadopago') = ?";
        $params[] = $paymentMethod;
    }

    if ($dateFrom !== null) {
        $where[] = "o.created_at >= ?";
        $params[] = $dateFrom . ' 00:00:00';
    }

    if ($dateTo !== null) {
        $where[] = "o.created_at < ?";
        $params[] = date('Y-m-d', strtotime($dateTo . ' +1 day')) . ' 00:00:00';
    }

    if ($queue !== null) {
        [$queueSql, $queueParams] = getQueueWhereClause($queue);
        if ($queueSql !== '') {
            $where[] = $queueSql;
            array_push($params, ...$queueParams);
        }
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $orderBy = in_array($queue, ['requires_action', 'manual_review', 'production', 'ready', 'delivery'], true)
        ? 'ORDER BY o.created_at ASC'
        : 'ORDER BY o.created_at DESC';

    $countStmt = $db->prepare("SELECT COUNT(*) FROM orders o $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "
        SELECT
            o.*,
            au.username AS payment_verified_by_username
        FROM orders o
        LEFT JOIN admin_users au ON au.id = o.payment_verified_by
        $whereSql
        $orderBy
        LIMIT ? OFFSET ?
    ";
    $stmt = $db->prepare($sql);
    bindListParams($stmt, array_merge($params, [$limit, $offset]));
    $stmt->execute();

    $orders = [];
    foreach ($stmt->fetchAll() as $row) {
        $orders[] = hydrateAdminOrder($row);
    }

    return [
        'orders' => $orders,
        'count' => count($orders),
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => max(1, (int)ceil($total / $limit)),
        'filters' => [
            'q' => $q,
            'payment_status' => $paymentStatus,
            'fulfillment_status' => $fulfillmentStatus,
            'payment_method' => $paymentMethod,
            'queue' => $queue,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ],
    ];
}

function getAdminOrderDetail(PDO $db, int $orderId): array {
    $stmt = $db->prepare("
        SELECT
            o.*,
            au.username AS payment_verified_by_username
        FROM orders o
        LEFT JOIN admin_users au ON au.id = o.payment_verified_by
        WHERE o.id = ?
        LIMIT 1
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonResponse(['error' => 'Order not found'], 404);
    }

    $order = hydrateAdminOrder($order);

    $itemsStmt = $db->prepare("
        SELECT
            oi.id,
            oi.product_id,
            oi.variant_id,
            oi.variant_label,
            oi.variant_primary_color,
            oi.variant_secondary_color,
            oi.quantity,
            oi.unit_price,
            p.name AS product_name,
            p.image_url
        FROM order_items oi
        LEFT JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll();
    foreach ($items as &$item) {
        $item['id'] = (int)$item['id'];
        $item['product_id'] = (int)$item['product_id'];
        $item['variant_id'] = isset($item['variant_id']) ? (int)$item['variant_id'] : null;
        $item['quantity'] = (int)$item['quantity'];
        $item['unit_price'] = (float)$item['unit_price'];
    }

    $order['items'] = $items;
    $order['shipping_address'] = pbGetOrderShippingAddress($db, $orderId);
    return $order;
}

function updateAdminOrder(PDO $db, int $orderId, array $body): array {
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonResponse(['error' => 'Order not found'], 404);
    }

    $hasLifecycleChange = array_key_exists('payment_status', $body)
        || array_key_exists('fulfillment_status', $body)
        || array_key_exists('status', $body);

    $requestedChanges = [];
    if (array_key_exists('payment_status', $body)) {
        $requestedChanges['payment_status'] = $body['payment_status'];
    }
    if (array_key_exists('fulfillment_status', $body)) {
        $requestedChanges['fulfillment_status'] = $body['fulfillment_status'];
    }
    if (array_key_exists('status', $body)) {
        $requestedChanges['status'] = $body['status'];
    }

    $nextLifecycle = pbResolveOrderLifecycle($order, $requestedChanges);
    $currentLifecycle = pbGetOrderLifecycle($order);

    $transitionError = pbLifecycleTransitionError($order, $nextLifecycle);
    if ($transitionError !== null) {
        jsonResponse(['error' => $transitionError], 422);
    }

    $notesProvided = array_key_exists('notes', $body);
    $paymentNotesProvided = array_key_exists('payment_notes', $body);
    $paymentReferenceProvided = array_key_exists('payment_reference', $body);

    $notes = $notesProvided ? normalizeOptionalText($body['notes'] ?? null) : $order['notes'];
    $paymentNotes = $paymentNotesProvided ? normalizeOptionalText($body['payment_notes'] ?? null) : $order['payment_notes'];
    $paymentReference = $paymentReferenceProvided ? normalizeOptionalText($body['payment_reference'] ?? null, 120) : $order['payment_reference'];

    $paymentVerifiedAt = $order['payment_verified_at'];
    $paymentVerifiedBy = $order['payment_verified_by'];

    if ($hasLifecycleChange && $nextLifecycle['payment_status'] !== $currentLifecycle['payment_status']) {
        if ($nextLifecycle['payment_status'] === 'approved') {
            $paymentVerifiedAt = pbNowSql();
            $paymentVerifiedBy = (int)$_SESSION['admin_id'];
        } elseif (!in_array($nextLifecycle['payment_status'], ['approved'], true)) {
            $paymentVerifiedAt = null;
            $paymentVerifiedBy = null;
        }
    }

    $db->beginTransaction();

    try {
        if ($hasLifecycleChange) {
            pbApplyLifecycleTransitionEffects($db, $order, $nextLifecycle);
        }

        $updateStmt = $db->prepare("
            UPDATE orders
            SET
                status = ?,
                payment_status = ?,
                fulfillment_status = ?,
                checkout_status = ?,
                notes = ?,
                payment_notes = ?,
                payment_reference = ?,
                payment_verified_at = ?,
                payment_verified_by = ?
            WHERE id = ?
        ");
        $updateStmt->execute([
            $nextLifecycle['status'],
            $nextLifecycle['payment_status'],
            $nextLifecycle['fulfillment_status'],
            $nextLifecycle['checkout_status'],
            $notes,
            $paymentNotes,
            $paymentReference,
            $paymentVerifiedAt,
            $paymentVerifiedBy,
            $orderId,
        ]);

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    adminAuditLog('update', 'order', $orderId, [
        'payment_status_from' => $currentLifecycle['payment_status'],
        'payment_status_to' => $nextLifecycle['payment_status'],
        'fulfillment_status_from' => $currentLifecycle['fulfillment_status'],
        'fulfillment_status_to' => $nextLifecycle['fulfillment_status'],
        'notes_updated' => $notesProvided,
        'payment_notes_updated' => $paymentNotesProvided,
        'payment_reference_updated' => $paymentReferenceProvided,
    ]);

    return [
        'success' => true,
        'order' => getAdminOrderDetail($db, $orderId),
    ];
}

function hydrateAdminOrder(array $row): array {
    $lifecycle = pbGetOrderLifecycle($row);
    $row['id'] = (int)$row['id'];
    $row['total'] = (float)$row['total'];
    $row['status'] = $lifecycle['status'];
    $row['payment_status'] = $lifecycle['payment_status'];
    $row['fulfillment_status'] = $lifecycle['fulfillment_status'];
    $row['is_manual_payment'] = in_array(($row['payment_method'] ?? ''), ['transferencia', 'efectivo'], true);
    $row['queue_bucket'] = determineQueueBucket($row);
    return $row;
}

function determineQueueBucket(array $order): string {
    $paymentStatus = pbNormalizePaymentStatus($order['payment_status'] ?? 'pending');
    $fulfillmentStatus = pbNormalizeFulfillmentStatus($order['fulfillment_status'] ?? 'queued');
    $paymentMethod = strtolower(trim((string)($order['payment_method'] ?? 'mercadopago')));

    if (in_array($paymentStatus, ['rejected', 'cancelled', 'refunded', 'charged_back'], true) || $fulfillmentStatus === 'cancelled') {
        return 'completed';
    }

    if ($paymentMethod !== 'mercadopago' && in_array($paymentStatus, ['pending', 'under_review'], true)) {
        return 'manual_review';
    }

    if ($paymentStatus !== 'approved') {
        return 'requires_action';
    }

    return match ($fulfillmentStatus) {
        'queued', 'in_production' => 'production',
        'ready' => 'ready',
        'shipped' => 'delivery',
        'delivered' => 'completed',
        default => 'requires_action',
    };
}

function getQueueWhereClause(string $queue): array {
    return match ($queue) {
        'requires_action' => [
            "(
                (COALESCE(o.payment_method, 'mercadopago') IN ('transferencia', 'efectivo') AND COALESCE(o.payment_status, 'pending') IN ('pending', 'under_review'))
                OR (COALESCE(o.payment_status, 'pending') = 'approved' AND COALESCE(o.fulfillment_status, 'queued') IN ('queued', 'ready', 'shipped'))
                OR (COALESCE(o.payment_status, 'pending') = 'pending' AND COALESCE(o.payment_method, 'mercadopago') = 'mercadopago')
            )",
            [],
        ],
        'manual_review' => [
            "COALESCE(o.payment_method, 'mercadopago') IN ('transferencia', 'efectivo') AND COALESCE(o.payment_status, 'pending') IN ('pending', 'under_review')",
            [],
        ],
        'production' => [
            "COALESCE(o.payment_status, 'pending') = 'approved' AND COALESCE(o.fulfillment_status, 'queued') IN ('queued', 'in_production')",
            [],
        ],
        'ready' => [
            "COALESCE(o.payment_status, 'pending') = 'approved' AND COALESCE(o.fulfillment_status, 'queued') = 'ready'",
            [],
        ],
        'delivery' => [
            "COALESCE(o.payment_status, 'pending') = 'approved' AND COALESCE(o.fulfillment_status, 'queued') = 'shipped'",
            [],
        ],
        'completed' => [
            "COALESCE(o.fulfillment_status, 'queued') = 'delivered' OR COALESCE(o.payment_status, 'pending') IN ('rejected', 'cancelled', 'refunded', 'charged_back') OR COALESCE(o.fulfillment_status, 'queued') = 'cancelled'",
            [],
        ],
        default => ['', []],
    };
}

function bindListParams(PDOStatement $stmt, array $params): void {
    foreach (array_values($params) as $index => $value) {
        $stmt->bindValue($index + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
}

function normalizeOptionalEnum($value, array $allowed): ?string {
    $value = strtolower(trim((string)$value));
    if ($value === '') {
        return null;
    }

    return in_array($value, $allowed, true) ? $value : null;
}

function normalizeDateInput($value): ?string {
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $value);
    if (!$dt || $dt->format('Y-m-d') !== $value) {
        return null;
    }

    return $value;
}

function normalizeOptionalText($value, ?int $maxLength = null): ?string {
    if ($value === null) {
        return null;
    }

    if (is_array($value) || is_object($value)) {
        return null;
    }

    $text = trim((string)$value);
    if ($text === '') {
        return null;
    }

    if ($maxLength !== null) {
        $text = mb_substr($text, 0, $maxLength);
    }

    return $text;
}
