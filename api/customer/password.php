<?php
/**
 * Customer password change
 * POST /api/customer/password.php
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../customer_auth.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$session = pbCustomerRequireAuth($db, true);
$payload = getJsonBody();

$currentPassword = (string)($payload['current_password'] ?? '');
$password = (string)($payload['password'] ?? '');
$passwordConfirm = (string)($payload['password_confirm'] ?? '');

if ($currentPassword === '' || $password === '' || $passwordConfirm === '') {
    jsonResponse(['error' => 'La contraseña actual, la nueva y su confirmación son obligatorias.'], 400);
}

if ($password !== $passwordConfirm) {
    jsonResponse(['error' => 'Las contraseñas nuevas no coinciden.'], 400);
}

$passwordError = pbCustomerPasswordValidationError($password);
if ($passwordError !== null) {
    jsonResponse(['error' => $passwordError], 400);
}

$currentCustomer = pbCustomerFindByEmail($db, (string)$session['customer']['email']);
if (!$currentCustomer) {
    jsonResponse(['error' => 'Cuenta no encontrada.'], 404);
}

if (!password_verify($currentPassword, (string)$currentCustomer['password_hash'])) {
    jsonResponse(['error' => 'La contraseña actual no coincide.'], 400);
}

if (password_verify($password, (string)$currentCustomer['password_hash'])) {
    jsonResponse(['error' => 'La nueva contraseña debe ser distinta a la actual.'], 400);
}

try {
    $stmt = $db->prepare('UPDATE customers SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([
        password_hash($password, PASSWORD_DEFAULT),
        (int)$session['customer_id'],
    ]);

    $db->prepare('DELETE FROM customer_password_resets WHERE customer_id = ?')->execute([(int)$session['customer_id']]);

    jsonResponse([
        'success' => true,
        'message' => 'Contraseña actualizada correctamente.',
    ]);
} catch (Throwable $e) {
    error_log('customer/password error: ' . $e->getMessage());
    jsonResponse(['error' => 'No se pudo actualizar la contraseña.'], 500);
}
