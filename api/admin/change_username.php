<?php
/**
 * PrintingBruno - Admin API: Change Username
 * POST /api/admin/change_username.php
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

$body        = getJsonBody();
$newUsername = trim($body['new_username'] ?? '');
$password    = $body['password'] ?? '';

if ($newUsername === '' || $password === '') {
    jsonResponse(['error' => 'Todos los campos son requeridos'], 400);
}

if (strlen($newUsername) < 3) {
    jsonResponse(['error' => 'El usuario debe tener al menos 3 caracteres'], 400);
}

if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $newUsername)) {
    jsonResponse(['error' => 'El usuario solo puede contener letras, números, puntos, guiones y guiones bajos'], 400);
}

$db = getDB();

// Verificar contraseña actual
$stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
$stmt->execute([(int)$_SESSION['admin_id']]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['error' => 'La contraseña es incorrecta'], 400);
}

// Verificar que el nuevo username no esté en uso
$stmt = $db->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
$stmt->execute([$newUsername, (int)$_SESSION['admin_id']]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'Ese nombre de usuario ya está en uso'], 409);
}

$oldUsername = $_SESSION['admin_user'];
$stmt = $db->prepare("UPDATE admin_users SET username = ? WHERE id = ?");
$stmt->execute([$newUsername, (int)$_SESSION['admin_id']]);

$_SESSION['admin_user'] = $newUsername;

adminAuditLog('change_username', 'admin_users', (int)$_SESSION['admin_id'], [
    'old' => $oldUsername,
    'new' => $newUsername,
]);

jsonResponse(['success' => true, 'message' => 'Usuario actualizado correctamente', 'username' => $newUsername]);
