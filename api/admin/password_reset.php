<?php
/**
 * PrintingBruno - Admin password recovery
 * GET  /api/admin/password_reset.php?token=...   -> validate token
 * POST /api/admin/password_reset.php             -> request or complete reset
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/rate_limit.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/../email/admin_password_reset.php';

const PB_ADMIN_RESET_TTL_SECONDS = 3600;

function pbAdminResetTokenHash(string $token): string
{
    return hash('sha256', $token);
}

function pbFindAdminByResetToken(PDO $db, string $token): ?array
{
    if ($token === '') {
        return null;
    }

    $stmt = $db->prepare("
        SELECT id, username, email
        FROM admin_users
        WHERE password_reset_token_hash = ?
          AND password_reset_expires_at IS NOT NULL
          AND password_reset_expires_at >= NOW()
        LIMIT 1
    ");
    $stmt->execute([pbAdminResetTokenHash($token)]);
    return $stmt->fetch() ?: null;
}

function pbAdminPasswordValidationError(string $password): ?string
{
    if (strlen($password) < 12) {
        return 'La contraseña debe tener al menos 12 caracteres.';
    }

    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'La contraseña debe incluir al menos una letra y un número.';
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = trim((string)($_GET['token'] ?? ''));
    if ($token === '') {
        jsonResponse(['valid' => false, 'error' => 'Falta el token de recuperación.'], 400);
    }

    $adminUser = pbFindAdminByResetToken(getDB(), $token);
    if (!$adminUser) {
        jsonResponse(['valid' => false, 'error' => 'El enlace es inválido o ya venció.'], 400);
    }

    jsonResponse([
        'valid' => true,
        'username' => $adminUser['username'],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$payload = getJsonBody();
$action = trim((string)($payload['action'] ?? ''));
$db = getDB();

if ($action === 'request') {
    checkAndIncrementRateLimit(
        getRateLimitKey('admin_password_reset_request'),
        3,
        3600,
        3600,
        'Demasiadas solicitudes de recuperación. Intentá nuevamente en una hora.'
    );

    $identity = trim((string)($payload['identity'] ?? ''));
    if ($identity === '') {
        jsonResponse(['error' => 'Ingresá tu usuario o email.'], 400);
    }

    $stmt = $db->prepare("
        SELECT id, username, email
        FROM admin_users
        WHERE username = ? OR email = ?
        LIMIT 1
    ");
    $stmt->execute([$identity, $identity]);
    $adminUser = $stmt->fetch();

    if ($adminUser) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + PB_ADMIN_RESET_TTL_SECONDS);

        $update = $db->prepare("
            UPDATE admin_users
            SET password_reset_token_hash = ?,
                password_reset_expires_at = ?,
                password_reset_requested_at = NOW()
            WHERE id = ?
        ");
        $update->execute([pbAdminResetTokenHash($token), $expiresAt, (int)$adminUser['id']]);

        $emailSent = sendAdminPasswordResetEmail($adminUser, $token);
        adminAuditLog('password_reset_requested', 'admin_user', (int)$adminUser['id'], [
            'username' => $adminUser['username'],
            'email_sent' => $emailSent,
        ]);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Si el usuario existe, enviamos un enlace de recuperación al correo configurado.',
    ]);
}

if ($action === 'reset') {
    checkAndIncrementRateLimit(
        getRateLimitKey('admin_password_reset_confirm'),
        5,
        3600,
        3600,
        'Demasiados intentos de restablecimiento. Intentá nuevamente más tarde.'
    );

    $token = trim((string)($payload['token'] ?? ''));
    $password = (string)($payload['password'] ?? '');
    $passwordConfirm = (string)($payload['password_confirm'] ?? '');

    if ($token === '') {
        jsonResponse(['error' => 'Falta el token de recuperación.'], 400);
    }

    if ($password === '' || $passwordConfirm === '') {
        jsonResponse(['error' => 'Completá ambas contraseñas.'], 400);
    }

    if ($password !== $passwordConfirm) {
        jsonResponse(['error' => 'Las contraseñas no coinciden.'], 400);
    }

    $passwordError = pbAdminPasswordValidationError($password);
    if ($passwordError !== null) {
        jsonResponse(['error' => $passwordError], 400);
    }

    $adminUser = pbFindAdminByResetToken($db, $token);
    if (!$adminUser) {
        jsonResponse(['error' => 'El enlace es inválido o ya venció.'], 400);
    }

    $stmt = $db->prepare("
        UPDATE admin_users
        SET password_hash = ?,
            password_reset_token_hash = NULL,
            password_reset_expires_at = NULL,
            password_reset_requested_at = NULL
        WHERE id = ?
    ");
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), (int)$adminUser['id']]);

    adminAuditLog('password_reset_completed', 'admin_user', (int)$adminUser['id'], [
        'username' => $adminUser['username'],
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'La contraseña fue actualizada. Ya podés volver a ingresar al panel.',
    ]);
}

jsonResponse(['error' => 'Acción inválida.'], 400);
