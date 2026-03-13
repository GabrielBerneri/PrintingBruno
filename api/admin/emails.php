<?php
/**
 * PrintingBruno - Admin API: Email Logs
 * GET /api/admin/emails.php        -> List all email logs (without body_html for performance)
 * GET /api/admin/emails.php?id=X   -> Get full details of a specific email log including body_html
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db.php';

// Auth check
if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (!empty($_GET['id'])) {
            // Get specific log (with HTML body)
            $id = (int)$_GET['id'];
            $stmt = $db->prepare("
                SELECT e.*, o.order_number 
                FROM email_logs e
                LEFT JOIN orders o ON e.order_id = o.id
                WHERE e.id = ?
            ");
            $stmt->execute([$id]);
            $log = $stmt->fetch();
            
            if (!$log) {
                jsonResponse(['error' => 'Log not found'], 404);
            }
            jsonResponse($log);
            
        } else {
            // List all logs (exclude body_html to save bandwidth)
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 50;
            $offset = ($page - 1) * $limit;
            
            // Filter by status if provided
            $whereClause = "";
            $params = [];
            if (!empty($_GET['status']) && in_array($_GET['status'], ['sent', 'failed'])) {
                $whereClause = "WHERE e.status = ?";
                $params[] = $_GET['status'];
            }
            
            // Get total count for pagination
            $countStmt = $db->prepare("SELECT COUNT(*) FROM email_logs e $whereClause");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get logs
            $query = "
                SELECT e.id, e.order_id, o.order_number, e.recipient_email, e.subject, e.status, e.error_message, e.created_at 
                FROM email_logs e
                LEFT JOIN orders o ON e.order_id = o.id
                $whereClause
                ORDER BY e.created_at DESC
                LIMIT $limit OFFSET $offset
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $logs = $stmt->fetchAll();
            
            jsonResponse([
                'logs' => $logs,
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $limit)
            ]);
        }
    } else {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log('admin/emails error: ' . $e->getMessage());
    jsonResponse(['error' => 'Server error'], 500);
}
