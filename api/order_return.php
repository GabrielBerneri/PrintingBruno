<?php
/**
 * Safe checkout return context.
 * GET /api/order_return.php?order=123&ot=...
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/order_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $orderId = (int)($_GET['order'] ?? 0);
    $orderToken = trim((string)($_GET['ot'] ?? ''));

    if ($orderId <= 0) {
        jsonResponse(['error' => 'Order ID required'], 400);
    }

    $db = getDB();
    pbExpireReservations($db);

    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        jsonResponse(['error' => 'Order not found'], 404);
    }

    $orderNumber = (string)($order['order_number'] ?? '');
    if (!pbVerifyOrderAccessToken($orderId, $orderNumber, $orderToken)) {
        jsonResponse(['error' => 'Unauthorized'], 403);
    }

    $paymentMethod = strtolower(trim((string)($order['payment_method'] ?? 'mercadopago')));
    $lifecycle = pbGetOrderLifecycle($order);

    $transferDetails = null;
    if ($paymentMethod === 'transferencia') {
        $transferDetails = array_filter([
            'bank_name' => TRANSFER_BANK_NAME,
            'account_label' => TRANSFER_ACCOUNT_LABEL,
            'cbu' => TRANSFER_CBU,
            'alias' => TRANSFER_ALIAS,
            'account_holder' => TRANSFER_ACCOUNT_HOLDER,
        ], static fn($value) => trim((string)$value) !== '');
    }

    jsonResponse([
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'payment_method' => $paymentMethod,
        'payment_status' => $lifecycle['payment_status'],
        'fulfillment_status' => $lifecycle['fulfillment_status'],
        'status' => $lifecycle['status'],
        'transfer_details' => $transferDetails,
    ]);
} catch (Throwable $e) {
    error_log('order_return error: ' . $e->getMessage());
    jsonResponse(['error' => 'Error loading order return context'], 500);
}
