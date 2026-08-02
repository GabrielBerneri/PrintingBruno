<?php
/**
 * PrintingBruno - Admin API: Change Password
 * POST /api/admin/change_password.php
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/audit.php';

if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

adminRequireCsrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$body           = getJsonBody();
$currentPass    = $body['current_password'] ?? '';
$newPass        = $body['new_password'] ?? '';
$confirmPass    = $body['confirm_password'] ?? '';

if ($currentPass === '' || $newPass === '' || $confirmPass === '') {
    jsonResponse(['error' => 'Todos los campos son requeridos'], 400);
}

if ($newPass !== $confirmPass) {
    jsonResponse(['error' => 'Las contraseñas nuevas no coinciden'], 400);
}

if (strlen($newPass) < 8) {
    jsonResponse(['error' => 'La nueva contraseña debe tener al menos 8 caracteres'], 400);
}

$db   = getDB();
$stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
$stmt->execute([(int)$_SESSION['admin_id']]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPass, $user['password_hash'])) {
    jsonResponse(['error' => 'La contraseña actual es incorrecta'], 400);
}

$newHash = password_hash($newPass, PASSWORD_DEFAULT);
$stmt    = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
$stmt->execute([$newHash, (int)$_SESSION['admin_id']]);

adminAuditLog('change_password', 'admin_users', (int)$_SESSION['admin_id']);
jsonResponse(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
