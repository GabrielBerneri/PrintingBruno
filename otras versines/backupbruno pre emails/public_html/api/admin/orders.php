<?php
/**
 * PrintingBruno - Admin API: Orders
 * GET /api/admin/orders.php          → List all orders
 * GET /api/admin/orders.php?id=X     → Order detail with items
 * PUT /api/admin/orders.php?id=X     → Update order notes/status
 */

require_once __DIR__ . '/session.php';

require_once __DIR__ . '/../db.php';

if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$db = getDB();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!empty($_GET['id'])) {
            // Single order with items
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([(int)$_GET['id']]);
            $order = $stmt->fetch();
            
            if (!$order) jsonResponse(['error' => 'Order not found'], 404);
            
            $stmt = $db->prepare("SELECT oi.*, p.name as product_name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            $stmt->execute([$order['id']]);
            $order['items'] = $stmt->fetchAll();
            $order['total'] = (float)$order['total'];
            
            jsonResponse($order);
        } else {
            // List all orders
            $status = $_GET['status'] ?? null;
            $sql = "SELECT * FROM orders";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll();
            
            foreach ($orders as &$o) $o['total'] = (float)$o['total'];
            
            jsonResponse(['orders' => $orders, 'count' => count($orders)]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (empty($_GET['id'])) jsonResponse(['error' => 'Order ID required'], 400);
        
        $body = getJsonBody();
        $id = (int)$_GET['id'];
        
        $updates = [];
        $params = [];
        
        if (isset($body['notes'])) {
            $updates[] = 'notes = ?';
            $params[] = $body['notes'];
        }
        if (isset($body['status'])) {
            $allowedStatuses = ['pending', 'approved', 'in_process', 'rejected', 'refunded', 'shipped', 'delivered', 'cancelled'];
            if (!in_array($body['status'], $allowedStatuses, true)) {
                jsonResponse(['error' => 'Invalid status value'], 400);
            }
            $updates[] = 'status = ?';
            $params[] = $body['status'];
        }
        
        if (!empty($updates)) {
            $params[] = $id;
            $db->prepare("UPDATE orders SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
        }
        
        jsonResponse(['success' => true]);
    }
} catch (Exception $e) {
    jsonResponse(['error' => 'Server error'], 500);
}
