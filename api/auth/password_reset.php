<?php
/**
 * Customer password recovery
 * GET  /api/auth/password_reset.php?token=...
 * POST /api/auth/password_reset.php
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../customer_auth.php';
require_once __DIR__ . '/../security/rate_limit.php';
require_once __DIR__ . '/../email/customer_password_reset.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = trim((string)($_GET['token'] ?? ''));
    $reset = pbCustomerFindPasswordResetToken($db, $token);
    if (!$reset) {
        jsonResponse(['valid' => false, 'error' => 'El enlace es inválido o ya venció.'], 400);
    }

    jsonResponse([
        'valid' => true,
        'email' => $reset['email'] ?? '',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$payload = getJsonBody();
$action = trim((string)($payload['action'] ?? ''));

if ($action === 'request') {
    checkAndIncrementRateLimit(
        getRateLimitKey('customer_password_reset_request'),
        3,
        3600,
        3600,
        'Demasiadas solicitudes de recuperación. Intentá nuevamente en una hora.'
    );

    $email = pbCustomerNormalizeEmail($payload['email'] ?? '');
    if ($email === '') {
        jsonResponse(['error' => 'Ingresá tu email.'], 400);
    }

    $customer = pbCustomerFindByEmail($db, $email);
    if ($customer) {
        $token = pbCustomerCreatePasswordReset($db, (int)$customer['id']);
        sendCustomerPasswordResetEmail($customer, $token);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Si el email existe, enviamos un enlace para restablecer la contraseña.',
    ]);
}

if ($action === 'reset') {
    checkAndIncrementRateLimit(
        getRateLimitKey('customer_password_reset_confirm'),
        5,
        3600,
        3600,
        'Demasiados intentos de restablecimiento. Intentá nuevamente más tarde.'
    );

    $token = trim((string)($payload['token'] ?? ''));
    $password = (string)($payload['password'] ?? '');
    $passwordConfirm = (string)($payload['password_confirm'] ?? '');

    if ($token === '' || $password === '' || $passwordConfirm === '') {
        jsonResponse(['error' => 'Completá todos los campos.'], 400);
    }
    if ($password !== $passwordConfirm) {
        jsonResponse(['error' => 'Las contraseñas no coinciden.'], 400);
    }

    $passwordError = pbCustomerPasswordValidationError($password);
    if ($passwordError !== null) {
        jsonResponse(['error' => $passwordError], 400);
    }

    $reset = pbCustomerFindPasswordResetToken($db, $token);
    if (!$reset) {
        jsonResponse(['error' => 'El enlace es inválido o ya venció.'], 400);
    }

    $db->beginTransaction();
    try {
        $db->prepare('UPDATE customers SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), (int)$reset['customer_id']]);
        $db->prepare('UPDATE customer_password_resets SET used_at = NOW() WHERE id = ?')
            ->execute([(int)$reset['password_reset_id']]);
        $db->prepare('DELETE FROM customer_sessions WHERE customer_id = ?')
            ->execute([(int)$reset['customer_id']]);
        $db->commit();

        jsonResponse([
            'success' => true,
            'message' => 'La contraseña fue actualizada. Ya podés ingresar con la nueva clave.',
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('auth/password_reset error: ' . $e->getMessage());
        jsonResponse(['error' => 'No se pudo actualizar la contraseña.'], 500);
    }
}

jsonResponse(['error' => 'Acción inválida.'], 400);
