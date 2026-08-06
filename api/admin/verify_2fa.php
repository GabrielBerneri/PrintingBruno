<?php
/**
 * PrintingBruno - Admin 2FA: verificar código
 * POST /api/admin/verify_2fa.php  { "code": "123456" }
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/rate_limit.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/audit.php';

adminEnsureSessionStarted();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

if (empty($_SESSION['admin_pending_2fa']) || empty($_SESSION['admin_pending_id'])) {
    jsonResponse(['error' => 'No hay sesión pendiente de verificación'], 403);
}

$rateLimitKey = getRateLimitKey('admin_2fa_verify');
checkRateLimit($rateLimitKey);

$body = getJsonBody();
$code = trim((string)($body['code'] ?? ''));

if (!preg_match('/^\d{6}$/', $code)) {
    recordFailedAttempt($rateLimitKey);
    jsonResponse(['error' => 'Código inválido'], 400);
}

// Código expirado
if (empty($_SESSION['admin_2fa_expires_at']) || time() > (int)$_SESSION['admin_2fa_expires_at']) {
    session_unset();
    jsonResponse(['error' => 'El código expiró. Iniciá sesión nuevamente.'], 401);
}

// Verificar código
if (empty($_SESSION['admin_2fa_code']) || !password_verify($code, $_SESSION['admin_2fa_code'])) {
    recordFailedAttempt($rateLimitKey);
    $_SESSION['admin_2fa_attempts'] = ($_SESSION['admin_2fa_attempts'] ?? 0) + 1;
    if ((int)$_SESSION['admin_2fa_attempts'] >= 5) {
        session_unset();
        jsonResponse(['error' => 'Demasiados intentos incorrectos. Iniciá sesión nuevamente.'], 401);
    }
    jsonResponse(['error' => 'Código incorrecto'], 401);
}

// Código válido — completar el login
$pendingId   = (int)$_SESSION['admin_pending_id'];
$pendingUser = (string)$_SESSION['admin_pending_user'];

// Limpiar estado temporal
unset(
    $_SESSION['admin_pending_2fa'],
    $_SESSION['admin_pending_id'],
    $_SESSION['admin_pending_user'],
    $_SESSION['admin_2fa_code'],
    $_SESSION['admin_2fa_expires_at'],
    $_SESSION['admin_2fa_attempts']
);

session_regenerate_id(true);
$_SESSION['admin_id']         = $pendingId;
$_SESSION['admin_user']       = $pendingUser;
$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

clearRateLimit($rateLimitKey);
clearRateLimit(getRateLimitKey('admin_login'));

adminAuditLog('login_2fa', 'admin_session', $pendingId, ['username' => $pendingUser]);

jsonResponse(['success' => true, 'user' => $pendingUser, 'csrf_token' => adminCsrfToken()]);
