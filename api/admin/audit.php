<?php
/**
 * Admin audit trail helpers.
 */

require_once __DIR__ . '/../db.php';

function adminAuditLog(string $action, string $entityType, ?int $entityId = null, array $details = []): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO admin_audit_log (admin_user_id, action, entity_type, entity_id, details_json, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null,
            $action,
            $entityType,
            $entityId,
            !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('admin audit warning: ' . $e->getMessage());
    }
}
