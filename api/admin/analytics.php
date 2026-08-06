<?php
/**
 * PrintingBruno - Admin API: Analytics
 * GET /api/admin/analytics.php → Métricas de negocio
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db.php';

if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$db = getDB();

try {
    // Pedidos e ingresos por día (últimos 30 días)
    $stmt = $db->query("
        SELECT
            DATE(created_at) AS day,
            COUNT(*) AS orders,
            COALESCE(SUM(CASE WHEN payment_status = 'approved' THEN total ELSE 0 END), 0) AS revenue
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ");
    $byDay = $stmt->fetchAll();

    // KPIs del mes actual
    $stmt = $db->query("
        SELECT
            COUNT(*) AS orders_month,
            COALESCE(SUM(CASE WHEN payment_status = 'approved' THEN total ELSE 0 END), 0) AS revenue_month,
            COALESCE(AVG(CASE WHEN payment_status = 'approved' THEN total END), 0) AS avg_ticket
        FROM orders
        WHERE MONTH(created_at) = MONTH(CURDATE())
          AND YEAR(created_at) = YEAR(CURDATE())
    ");
    $monthKpis = $stmt->fetch();

    // Pedidos de hoy
    $stmt = $db->query("
        SELECT COUNT(*) AS orders_today
        FROM orders
        WHERE DATE(created_at) = CURDATE()
    ");
    $today = $stmt->fetch();

    // Distribución de métodos de pago (todos los tiempos)
    $stmt = $db->query("
        SELECT payment_method, COUNT(*) AS cnt
        FROM orders
        GROUP BY payment_method
        ORDER BY cnt DESC
    ");
    $paymentMethods = $stmt->fetchAll();

    // Top 5 categorías por ingresos
    $stmt = $db->query("
        SELECT p.category, COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.payment_status = 'approved'
        GROUP BY p.category
        ORDER BY revenue DESC
        LIMIT 5
    ");
    $byCategory = $stmt->fetchAll();

    jsonResponse([
        'by_day'          => $byDay,
        'orders_today'    => (int)$today['orders_today'],
        'orders_month'    => (int)$monthKpis['orders_month'],
        'revenue_month'   => (float)$monthKpis['revenue_month'],
        'avg_ticket'      => (float)$monthKpis['avg_ticket'],
        'payment_methods' => $paymentMethods,
        'by_category'     => $byCategory,
    ]);

} catch (Exception $e) {
    jsonResponse(['error' => 'Server error'], 500);
}
