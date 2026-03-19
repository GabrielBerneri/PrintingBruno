<?php
/**
 * Customer orders
 * GET /api/customer/orders.php
 * GET /api/customer/orders.php?id=X
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../customer_auth.php';
require_once __DIR__ . '/../order_utils.php';

$db = getDB();
$session = pbCustomerRequireAuth($db, false);
$customerId = (int)$session['customer_id'];

if (!empty($_GET['id'])) {
    $orderId = (int)$_GET['id'];
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ? AND customer_id = ? LIMIT 1');
    $stmt->execute([$orderId, $customerId]);
    $order = $stmt->fetch();
    if (!$order) {
        jsonResponse(['error' => 'Order not found'], 404);
    }
    jsonResponse(['order' => pbCustomerHydrateOrderDetail($db, $order)]);
}

$stmt = $db->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
$stmt->execute([$customerId]);
$orders = [];
foreach ($stmt->fetchAll() as $row) {
    $orders[] = pbCustomerHydrateOrderSummary($row);
}

jsonResponse(['orders' => $orders]);

function pbCustomerHydrateOrderSummary(array $order): array {
    $lifecycle = pbGetOrderLifecycle($order);
    return [
        'id' => (int)$order['id'],
        'order_number' => $order['order_number'] ?? ('#' . $order['id']),
        'created_at' => $order['created_at'],
        'total' => (float)$order['total'],
        'payment_method' => $order['payment_method'] ?? 'mercadopago',
        'status' => $lifecycle['status'],
        'payment_status' => $lifecycle['payment_status'],
        'fulfillment_status' => $lifecycle['fulfillment_status'],
    ];
}

function pbCustomerHydrateOrderDetail(PDO $db, array $order): array {
    $detail = pbCustomerHydrateOrderSummary($order);
    $detail['notes'] = $order['notes'] ?? null;
    $detail['payment_reference'] = $order['payment_reference'] ?? null;

    $stmt = $db->prepare("
        SELECT oi.*, p.name AS product_name, p.image_url
        FROM order_items oi
        LEFT JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    $stmt->execute([(int)$order['id']]);
    $items = [];
    foreach ($stmt->fetchAll() as $item) {
        $items[] = [
            'id' => (int)$item['id'],
            'product_id' => (int)$item['product_id'],
            'variant_id' => isset($item['variant_id']) ? (int)$item['variant_id'] : null,
            'product_name' => $item['product_name'] ?? 'Producto',
            'variant_label' => $item['variant_label'] ?? null,
            'quantity' => (int)$item['quantity'],
            'unit_price' => (float)$item['unit_price'],
            'image_url' => $item['image_url'] ?? null,
        ];
    }
    $detail['items'] = $items;

    return $detail;
}
