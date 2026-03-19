<?php
/**
 * PrintingBruno - Admin API: Dashboard Metrics
 * GET /api/admin/dashboard.php → Aggregated KPIs
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../order_utils.php';

if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$db = getDB();
pbExpireReservations($db);

try {
    // Revenue & order counts
    $stmt = $db->query("
        SELECT
            COUNT(*) AS total_orders,
            COALESCE(SUM(CASE WHEN payment_status = 'approved' THEN total ELSE 0 END), 0) AS total_revenue,
            COALESCE(SUM(CASE WHEN payment_status = 'approved' THEN 1 ELSE 0 END), 0) AS approved_orders,
            COALESCE(SUM(CASE WHEN payment_status IN ('pending', 'under_review') THEN 1 ELSE 0 END), 0) AS pending_orders,
            COALESCE(SUM(CASE WHEN payment_status IN ('rejected', 'cancelled', 'charged_back') THEN 1 ELSE 0 END), 0) AS rejected_orders
        FROM orders
    ");
    $orderStats = $stmt->fetch();

    // Product counts
    $stmt = $db->query("
        SELECT
            COUNT(*) AS total_products,
            COALESCE(SUM(CASE WHEN p.active = 1 THEN 1 ELSE 0 END), 0) AS active_products,
            COALESCE(SUM(CASE WHEN p.active = 1 AND (p.stock - COALESCE(r.active_reserved, 0)) < 5 THEN 1 ELSE 0 END), 0) AS low_stock_count
        FROM products p
        LEFT JOIN (
            SELECT product_id, SUM(quantity) AS active_reserved
            FROM stock_reservations
            WHERE status = 'active'
            GROUP BY product_id
        ) r ON r.product_id = p.id
    ");
    $productStats = $stmt->fetch();

    // Low stock products (active, stock < 5)
    $stmt = $db->query("
        SELECT p.id, p.name, (p.stock - COALESCE(r.active_reserved, 0)) AS stock, p.category, p.image_url
        FROM products p
        LEFT JOIN (
            SELECT product_id, SUM(quantity) AS active_reserved
            FROM stock_reservations
            WHERE status = 'active'
            GROUP BY product_id
        ) r ON r.product_id = p.id
        WHERE p.active = 1 AND (p.stock - COALESCE(r.active_reserved, 0)) < 5
        ORDER BY stock ASC
        LIMIT 10
    ");
    $lowStockProducts = $stmt->fetchAll();

    // Top 5 best-selling products
    $stmt = $db->query("
        SELECT p.id, p.name, p.image_url, p.price, p.stock,
               COALESCE(SUM(CASE WHEN o.payment_status = 'approved' THEN oi.quantity ELSE 0 END), 0) AS total_sold
        FROM products p
        LEFT JOIN order_items oi ON oi.product_id = p.id
        LEFT JOIN orders o ON o.id = oi.order_id
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $topProducts = $stmt->fetchAll();
    foreach ($topProducts as &$tp) {
        $tp['price'] = (float)$tp['price'];
        $tp['total_sold'] = (int)$tp['total_sold'];
    }

    // Recent orders (last 5)
    $stmt = $db->query("
        SELECT id, order_number, customer_name, total, status, payment_status, fulfillment_status, created_at
        FROM orders
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $recentOrders = $stmt->fetchAll();
    foreach ($recentOrders as &$ro) {
        $ro['total'] = (float)$ro['total'];
        $lifecycle = pbGetOrderLifecycle($ro);
        $ro['status'] = $lifecycle['status'];
        $ro['payment_status'] = $lifecycle['payment_status'];
        $ro['fulfillment_status'] = $lifecycle['fulfillment_status'];
    }

    jsonResponse([
        'revenue'         => (float)$orderStats['total_revenue'],
        'total_orders'    => (int)$orderStats['total_orders'],
        'approved_orders' => (int)$orderStats['approved_orders'],
        'pending_orders'  => (int)$orderStats['pending_orders'],
        'rejected_orders' => (int)$orderStats['rejected_orders'],
        'total_products'  => (int)$productStats['total_products'],
        'active_products' => (int)$productStats['active_products'],
        'low_stock_count' => (int)$productStats['low_stock_count'],
        'low_stock'       => $lowStockProducts,
        'top_products'    => $topProducts,
        'recent_orders'   => $recentOrders,
    ]);

} catch (Exception $e) {
    jsonResponse(['error' => 'Server error'], 500);
}
