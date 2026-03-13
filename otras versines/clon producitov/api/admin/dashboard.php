<?php
/**
 * PrintingBruno - Admin API: Dashboard Metrics
 * GET /api/admin/dashboard.php → Aggregated KPIs
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db.php';

if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$db = getDB();

try {
    // Revenue & order counts
    $stmt = $db->query("
        SELECT
            COUNT(*) AS total_orders,
            COALESCE(SUM(CASE WHEN status = 'approved' THEN total ELSE 0 END), 0) AS total_revenue,
            COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) AS approved_orders,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_orders,
            COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected_orders
        FROM orders
    ");
    $orderStats = $stmt->fetch();

    // Product counts
    $stmt = $db->query("
        SELECT
            COUNT(*) AS total_products,
            COALESCE(SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END), 0) AS active_products,
            COALESCE(SUM(CASE WHEN stock < 5 AND active = 1 THEN 1 ELSE 0 END), 0) AS low_stock_count
        FROM products
    ");
    $productStats = $stmt->fetch();

    // Low stock products (active, stock < 5)
    $stmt = $db->query("
        SELECT id, name, stock, category, image_url
        FROM products
        WHERE active = 1 AND stock < 5
        ORDER BY stock ASC
        LIMIT 10
    ");
    $lowStockProducts = $stmt->fetchAll();

    // Top 5 best-selling products
    $stmt = $db->query("
        SELECT p.id, p.name, p.image_url, p.price, p.stock,
               COALESCE(SUM(oi.quantity), 0) AS total_sold
        FROM products p
        LEFT JOIN order_items oi ON oi.product_id = p.id
        LEFT JOIN orders o ON o.id = oi.order_id AND o.status = 'approved'
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
        SELECT id, order_number, customer_name, total, status, created_at
        FROM orders
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $recentOrders = $stmt->fetchAll();
    foreach ($recentOrders as &$ro) {
        $ro['total'] = (float)$ro['total'];
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
