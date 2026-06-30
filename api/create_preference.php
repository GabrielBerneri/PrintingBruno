<?php
/**
 * PrintingBruno - MercadoPago Checkout Pro
 * POST /api/create_preference.php
 * 
 * Body: {
 *   "items": [{ "id": 1, "variant_id": 10, "quantity": 2 }, ...],
 *   "customer": { "name": "...", "email": "...", "phone": "..." },
 *   "shipping_address": { "customer_address_id": 1, "recipient_name": "...", "street": "...", "city": "...", "province": "...", "postal_code": "..." }
 * }
 * 
 * Returns: { "init_point": "https://...", "preference_id": "...", "order_id": ... }
 */

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('create_preference_shutdown: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Error del servidor. Por favor intente nuevamente.']);
        exit;
    }
});

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security/rate_limit.php';
require_once __DIR__ . '/order_utils.php';
require_once __DIR__ . '/order_shipping.php';
require_once __DIR__ . '/customer_auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$checkoutKey = getRateLimitKey('checkout');
checkAndIncrementRateLimit($checkoutKey, 10, 900, 900, 'Demasiados intentos. Esperá unos minutos.');

try {
    $body = getJsonBody();
    
    // Validate input
    if (empty($body['items']) || !is_array($body['items'])) {
        jsonResponse(['error' => 'Items are required'], 400);
    }

    $db = getDB();
    $customerSession = null;
    if (pbHasTable($db, 'customer_sessions') && pbHasTable($db, 'customers')) {
        $customerSession = pbCustomerGetCurrentSession($db, true);
    }

    if ($customerSession) {
        $body['customer']['name'] = trim((string)($body['customer']['name'] ?? '')) !== ''
            ? trim((string)$body['customer']['name'])
            : ($customerSession['customer']['full_name'] ?: $customerSession['customer']['first_name']);
        $body['customer']['email'] = trim((string)($body['customer']['email'] ?? '')) !== ''
            ? trim((string)$body['customer']['email'])
            : (string)$customerSession['customer']['email'];
        $body['customer']['phone'] = trim((string)($body['customer']['phone'] ?? '')) !== ''
            ? trim((string)$body['customer']['phone'])
            : (string)($customerSession['customer']['phone'] ?? '');
    }

    if (empty($body['customer']['name']) || empty($body['customer']['email'])) {
        jsonResponse(['error' => 'Customer name and email are required'], 400);
    }
    if (!filter_var($body['customer']['email'], FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Email inválido'], 400);
    }

    pbExpireReservations($db);

    $idempotencyKey = pbBuildCheckoutIdempotencyKey($body);
    $existingOrder = pbFindOrderByIdempotencyKey($db, $idempotencyKey);
    $reuseExistingOrder = null;
    if ($existingOrder) {
        $reuseExistingOrder = respondWithExistingOrder($existingOrder);
    }

    // Fetch products from DB and calculate total
    $productIds = [];
    $variantIds = [];
    foreach ($body['items'] as $item) {
        $productIds[] = (int)($item['product_id'] ?? $item['id'] ?? 0);
        if (!empty($item['variant_id'])) {
            $variantIds[] = (int)$item['variant_id'];
        }
    }
    $productIds = array_values(array_unique(array_filter($productIds)));
    $variantIds = array_values(array_unique(array_filter($variantIds)));

    if (empty($productIds)) {
        jsonResponse(['error' => 'Items inválidos'], 400);
    }

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND active = 1");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();

    // Index by ID
    $productsById = [];
    foreach ($products as $p) {
        $productsById[$p['id']] = $p;
    }

    $reservedByProduct = pbGetReservedQuantities($db, $productIds, $reuseExistingOrder ? (int)$reuseExistingOrder['id'] : null);
    $variantsById = [];
    $reservedByVariant = [];
    $defaultVariantByProduct = [];
    if (pbHasProductVariantsTable($db)) {
        $variantGroups = pbFetchProductVariantsByProductIds($db, $productIds, true, $reuseExistingOrder ? (int)$reuseExistingOrder['id'] : null);
        foreach ($variantGroups as $productId => $variants) {
            foreach ($variants as $variant) {
                $variantsById[(int)$variant['id']] = $variant;
            }
            foreach ($variants as $variant) {
                if ((int)($variant['active'] ?? 0) !== 1) {
                    continue;
                }
                $defaultVariantByProduct[(int)$productId] = $variant;
                break;
            }
        }
        $variantIdsForReservation = !empty($variantIds) ? $variantIds : array_keys($variantsById);
        if (!empty($variantIdsForReservation)) {
            $reservedByVariant = pbGetReservedVariantQuantities($db, $variantIdsForReservation, $reuseExistingOrder ? (int)$reuseExistingOrder['id'] : null);
        }
    }

    // Build MP items and calculate total
    $mpItems = [];
    $total = 0;
    $orderItems = [];
    $hasVariantColumns = pbHasColumn($db, 'order_items', 'variant_id');

    $paymentMethod = $body['payment_method'] ?? 'mercadopago';
    if (!in_array($paymentMethod, ['mercadopago', 'transferencia', 'efectivo'])) {
        $paymentMethod = 'mercadopago';
    }
    $applyTransferDiscount = in_array($paymentMethod, ['transferencia', 'efectivo']);

    foreach ($body['items'] as $item) {
        $pid = (int)($item['product_id'] ?? $item['id'] ?? 0);
        $requestedVariantId = (int)($item['variant_id'] ?? 0);
        $qty = max(1, min(99, (int)($item['quantity'] ?? 1))); // límite: 1-99 unidades

        if (!isset($productsById[$pid])) {
            jsonResponse(['error' => "Product ID $pid not found"], 400);
        }

        $product = $productsById[$pid];
        $variant = null;

        if ($requestedVariantId > 0) {
            if (!isset($variantsById[$requestedVariantId])) {
                jsonResponse(['error' => 'La variante seleccionada ya no está disponible'], 400);
            }
            $variant = $variantsById[$requestedVariantId];
            if ((int)($variant['product_id'] ?? 0) !== $pid) {
                jsonResponse(['error' => 'La variante seleccionada no coincide con el producto'], 400);
            }
            if ((int)($variant['active'] ?? 0) !== 1) {
                jsonResponse(['error' => 'La variante seleccionada está inactiva'], 400);
            }
        } elseif (isset($defaultVariantByProduct[$pid])) {
            $variant = $defaultVariantByProduct[$pid];
        }

        // Verificar stock disponible considerando reservas activas
        if ($variant) {
            $variantId = (int)$variant['id'];
            $availableStock = max(0, (int)$variant['stock'] - (int)($reservedByVariant[$variantId] ?? 0));
        } else {
            $availableStock = max(0, (int)$product['stock'] - (int)($reservedByProduct[$pid] ?? 0));
        }
        if ($availableStock < $qty) {
            $itemName = $product['name'];
            if ($variant && !pbIsDefaultVariantLabel($variant['label'] ?? '')) {
                $itemName .= ' · ' . $variant['label'];
            }
            jsonResponse(['error' => "Stock insuficiente para {$itemName}. Disponible: {$availableStock}"], 400);
        }

        $price = $variant && $variant['price'] !== null
            ? (float)$variant['price']
            : (float)$product['price'];
        $discountPercent = max(0, min(100, (int)($product['transfer_discount'] ?? 0)));
        if ($applyTransferDiscount && $discountPercent > 0) {
            $price = round($price * (1 - $discountPercent / 100), 2);
        }
        $total += $price * $qty;
        $variantLabel = $variant ? pbBuildVariantLabel(
            $variant['primary_color'] ?? null,
            $variant['secondary_color'] ?? null,
            $variant['label'] ?? null,
            'Base'
        ) : null;
        $title = $product['name'];
        if ($variantLabel !== null && !pbIsDefaultVariantLabel($variantLabel)) {
            $title .= ' · ' . $variantLabel;
        }
        $pictureUrl = '';
        if ($variant && !empty($variant['image_url'])) {
            $pictureUrl = $variant['image_url'];
        } elseif (!empty($product['image_url'])) {
            $pictureUrl = $product['image_url'];
        }

        $mpItems[] = [
            'id' => (string)($variant ? ('v-' . (int)$variant['id']) : $pid),
            'title' => $title,
            'description' => mb_substr($product['description'] ?? '', 0, 200),
            'quantity' => $qty,
            'unit_price' => $price,
            'currency_id' => 'ARS',
            'picture_url' => $pictureUrl !== '' ? SITE_URL . '/' . $pictureUrl : null,
        ];

        $orderItem = [
            'product_id' => $pid,
            'variant_id' => $variant ? (int)$variant['id'] : null,
            'variant_label' => $variantLabel,
            'variant_primary_color' => $variant['primary_color'] ?? null,
            'variant_secondary_color' => $variant['secondary_color'] ?? null,
            'quantity' => $qty,
            'unit_price' => $price,
        ];
        if (!$hasVariantColumns) {
            unset($orderItem['variant_id'], $orderItem['variant_label'], $orderItem['variant_primary_color'], $orderItem['variant_secondary_color']);
        }
        $orderItems[] = $orderItem;
    }
    
    // Create order in DB
    $linkedCustomerId = null;
    if ($customerSession && pbCustomerNormalizeEmail($body['customer']['email'] ?? '') === pbCustomerNormalizeEmail($customerSession['customer']['email'] ?? '')) {
        $linkedCustomerId = (int)$customerSession['customer_id'];
    }
    $hasOrderCustomerIdColumn = pbHasColumn($db, 'orders', 'customer_id');
    $shippingSnapshot = pbResolveOrderShippingSnapshot(
        $db,
        $linkedCustomerId,
        $customerSession['customer'] ?? [
            'full_name' => $body['customer']['name'] ?? '',
            'phone' => $body['customer']['phone'] ?? '',
        ],
        isset($body['shipping_address']) && is_array($body['shipping_address']) ? $body['shipping_address'] : null
    );

    if ($reuseExistingOrder) {
        $orderId = (int)$reuseExistingOrder['id'];
        $orderNumber = (string)$reuseExistingOrder['order_number'];
        $total = (float)$reuseExistingOrder['total'];
        if ($linkedCustomerId && $hasOrderCustomerIdColumn && empty($reuseExistingOrder['customer_id'])) {
            $db->prepare('UPDATE orders SET customer_id = ? WHERE id = ? AND customer_id IS NULL')->execute([$linkedCustomerId, $orderId]);
        }
        if ($shippingSnapshot) {
            pbSaveOrderShippingAddress($db, $orderId, $shippingSnapshot);
        }
    } else {
        $db->beginTransaction();

        if ($hasOrderCustomerIdColumn) {
            $stmt = $db->prepare("
                INSERT INTO orders (
                    order_number, idempotency_key, customer_id, customer_name, customer_email, customer_phone, total,
                    status, payment_status, fulfillment_status, checkout_status, email_status, payment_method, mp_preference_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', 'queued', 'pending', 'pending', ?, ?)
            ");
        } else {
            $stmt = $db->prepare("
                INSERT INTO orders (
                    order_number, idempotency_key, customer_name, customer_email, customer_phone, total,
                    status, payment_status, fulfillment_status, checkout_status, email_status, payment_method, mp_preference_status
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending', 'queued', 'pending', 'pending', ?, ?)
            ");
        }
        $mpPreferenceStatus = $paymentMethod === 'mercadopago' ? 'pending' : 'not_required';
        $params = [
            null,
            $idempotencyKey,
        ];
        if ($hasOrderCustomerIdColumn) {
            $params[] = $linkedCustomerId;
        }
        array_push(
            $params,
            $body['customer']['name'],
            $body['customer']['email'],
            $body['customer']['phone'] ?? null,
            $total,
            $paymentMethod,
            $mpPreferenceStatus
        );
        $stmt->execute($params);
        $orderId = (int)$db->lastInsertId();

        // Generate order number: PB-YYYYMMDD-NNNN
        $orderNumber = 'PB-' . date('Ymd') . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT);
        $db->prepare("UPDATE orders SET order_number = ? WHERE id = ?")->execute([$orderNumber, $orderId]);

        // Insert order items
        if ($hasVariantColumns) {
            $stmt = $db->prepare("
                INSERT INTO order_items (
                    order_id, product_id, variant_id, variant_label, variant_primary_color, variant_secondary_color, quantity, unit_price
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($orderItems as $oi) {
                $stmt->execute([
                    $orderId,
                    $oi['product_id'],
                    $oi['variant_id'],
                    $oi['variant_label'],
                    $oi['variant_primary_color'],
                    $oi['variant_secondary_color'],
                    $oi['quantity'],
                    $oi['unit_price'],
                ]);
            }
        } else {
            $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            foreach ($orderItems as $oi) {
                $stmt->execute([$orderId, $oi['product_id'], $oi['quantity'], $oi['unit_price']]);
            }
        }

        pbCreateReservations($db, $orderId, $orderItems);
        if ($shippingSnapshot) {
            pbSaveOrderShippingAddress($db, $orderId, $shippingSnapshot);
        }

        $db->commit();

        // Send order confirmation email as best effort.
        require_once __DIR__ . '/email/order_confirmation.php';
        sendOrderConfirmation($orderId);
    }

    if ($paymentMethod !== 'mercadopago') {
        $returnQuery = pbBuildOrderReturnQuery($orderId, $orderNumber);
        $db->prepare("UPDATE orders SET checkout_status = 'ready', updated_at = NOW() WHERE id = ?")->execute([$orderId]);
        jsonResponse([
            'success_url' => MP_SUCCESS_URL . '?' . $returnQuery,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total' => $total,
        ]);
    }

    // Configure MercadoPago
    MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);
    $returnQuery = pbBuildOrderReturnQuery($orderId, $orderNumber);

    // Create preference
    $client = new PreferenceClient();
    $preference = $client->create([
        'items' => $mpItems,
        'payer' => [
            'name' => $body['customer']['name'],
            'email' => $body['customer']['email'],
            'phone' => [
                'number' => $body['customer']['phone'] ?? '',
            ],
        ],
        'back_urls' => [
            'success' => MP_SUCCESS_URL . '?' . $returnQuery,
            'failure' => MP_FAILURE_URL . '?' . $returnQuery,
            'pending' => MP_PENDING_URL . '?' . $returnQuery,
        ],
        'auto_return' => 'approved',
        'notification_url' => MP_WEBHOOK_URL,
        'external_reference' => (string)$orderId,
        'statement_descriptor' => SITE_NAME,
    ]);

    // Update order with preference ID
    $stmt = $db->prepare("
        UPDATE orders
        SET mp_preference_id = ?, mp_init_point = ?, mp_preference_status = 'created', checkout_status = 'ready', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$preference->id, $preference->init_point, $orderId]);

    jsonResponse([
        'init_point' => $preference->init_point,
        'preference_id' => $preference->id,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'total' => $total,
    ]);
    
} catch (MPApiException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    // No exponer detalles internos de MP al frontend
    error_log('MercadoPago API error: ' . json_encode($e->getApiResponse()->getContent()));
    if (!empty($orderId)) {
        getDB()->prepare("UPDATE orders SET mp_preference_status = 'failed', checkout_status = 'failed', updated_at = NOW() WHERE id = ?")
            ->execute([(int)$orderId]);
    }
    jsonResponse([
        'error' => 'Error al procesar el pago. Por favor intente nuevamente.',
        'order_id' => $orderId ?? null,
    ], 500);
} catch (InvalidArgumentException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    jsonResponse(['error' => $e->getMessage()], 400);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('create_preference error: ' . $e->getMessage());
    jsonResponse(['error' => 'Error del servidor. Por favor intente nuevamente.'], 500);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('create_preference fatal: ' . $e->getMessage());
    jsonResponse(['error' => 'Error del servidor. Por favor intente nuevamente.'], 500);
}

function respondWithExistingOrder(array $order): ?array {
    $orderId = (int)$order['id'];
    $orderNumber = $order['order_number'] ?: ('PB-' . date('Ymd') . '-' . str_pad((string)$orderId, 4, '0', STR_PAD_LEFT));
    $total = (float)$order['total'];
    $paymentMethod = $order['payment_method'] ?? 'mercadopago';
    $returnQuery = pbBuildOrderReturnQuery($orderId, $orderNumber);

    if ($paymentMethod !== 'mercadopago') {
        jsonResponse([
            'success_url' => MP_SUCCESS_URL . '?' . $returnQuery,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total' => $total,
            'reused' => true,
        ]);
    }

    if (!empty($order['mp_init_point'])) {
        jsonResponse([
            'init_point' => $order['mp_init_point'],
            'preference_id' => $order['mp_preference_id'],
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total' => $total,
            'reused' => true,
        ]);
    }

    $order['order_number'] = $orderNumber;
    $order['total'] = $total;
    return $order;
}
