<?php
/**
 * Customer orders
 * GET /api/customer/orders.php
 * GET /api/customer/orders.php?id=X
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../customer_auth.php';
require_once __DIR__ . '/../order_utils.php';
require_once __DIR__ . '/../order_shipping.php';

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
$rows = $stmt->fetchAll();
$previewMap = pbCustomerLoadOrderPreviewMap($db, array_map(static fn(array $row): int => (int)$row['id'], $rows));
$orders = [];
foreach ($rows as $row) {
    $orders[] = pbCustomerHydrateOrderSummary($row, $previewMap[(int)$row['id']] ?? null);
}

jsonResponse(['orders' => $orders]);

function pbCustomerHydrateOrderSummary(array $order, ?array $preview = null): array {
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
        'item_count' => (int)($preview['item_count'] ?? 0),
        'units_count' => (int)($preview['units_count'] ?? 0),
        'first_item' => $preview['first_item'] ?? null,
    ];
}

function pbCustomerHydrateOrderDetail(PDO $db, array $order): array {
    $detail = pbCustomerHydrateOrderSummary($order);
    $detail['notes'] = $order['notes'] ?? null;
    $detail['payment_reference'] = $order['payment_reference'] ?? null;
    $detail['shipping_address'] = pbGetOrderShippingAddress($db, (int)$order['id']);

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

function pbCustomerLoadOrderPreviewMap(PDO $db, array $orderIds): array {
    $orderIds = array_values(array_filter(array_map(static fn($id): int => (int)$id, $orderIds)));
    if ($orderIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $aggregateStmt = $db->prepare("
        SELECT order_id, COUNT(*) AS item_count, COALESCE(SUM(quantity), 0) AS units_count, MIN(id) AS first_item_id
        FROM order_items
        WHERE order_id IN ({$placeholders})
        GROUP BY order_id
    ");
    $aggregateStmt->execute($orderIds);

    $previewMap = [];
    $firstItemIds = [];
    foreach ($aggregateStmt->fetchAll() as $row) {
        $orderId = (int)$row['order_id'];
        $previewMap[$orderId] = [
            'item_count' => (int)$row['item_count'],
            'units_count' => (int)$row['units_count'],
            'first_item_id' => (int)$row['first_item_id'],
            'first_item' => null,
        ];
        if (!empty($row['first_item_id'])) {
            $firstItemIds[] = (int)$row['first_item_id'];
        }
    }

    if ($firstItemIds !== []) {
        $itemPlaceholders = implode(',', array_fill(0, count($firstItemIds), '?'));
        $itemStmt = $db->prepare("
            SELECT oi.id, oi.order_id, oi.product_id, oi.variant_id, oi.variant_label, oi.quantity, oi.unit_price, p.name AS product_name, p.image_url
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.id IN ({$itemPlaceholders})
        ");
        $itemStmt->execute($firstItemIds);
        foreach ($itemStmt->fetchAll() as $item) {
            $orderId = (int)$item['order_id'];
            if (!isset($previewMap[$orderId])) {
                continue;
            }
            $previewMap[$orderId]['first_item'] = [
                'id' => (int)$item['id'],
                'product_id' => (int)$item['product_id'],
                'variant_id' => isset($item['variant_id']) ? (int)$item['variant_id'] : null,
                'product_name' => $item['product_name'] ?? 'Producto',
                'variant_label' => $item['variant_label'] ?? null,
                'quantity' => (int)$item['quantity'],
                'unit_price' => (float)$item['unit_price'],
                'image_url' => $item['image_url'] ?? null,
            ];
            unset($previewMap[$orderId]['first_item_id']);
        }
    }

    foreach ($previewMap as $orderId => $data) {
        unset($previewMap[$orderId]['first_item_id']);
    }

    return $previewMap;
}
