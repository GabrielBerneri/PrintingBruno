<?php
/**
 * PrintingBruno - Sesión admin segura
 * Incluir ANTES de session_start() en todos los endpoints admin.
 *
 * Configura cookies con HttpOnly, Secure y SameSite=Strict para
 * proteger contra XSS (robo de cookie) y CSRF.
 */

session_name('pb_admin_session');

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Vary: Cookie');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
}

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$cookiePath = rtrim((string)dirname($scriptName), '/');
if ($cookiePath === '' || $cookiePath === '.') {
    $cookiePath = '/api/admin';
}
$cookiePath .= '/';

session_set_cookie_params([
    'lifetime' => 3600 * 8,   // 8 horas (igual que ADMIN_SESSION_LIFETIME)
    'path'     => $cookiePath,
    'domain'   => '',          // usa el dominio actual automáticamente
    'secure'   => $isHttps,    // solo HTTPS en producción; funciona en HTTP local
    'httponly' => true,        // JS no puede leer la cookie → mitiga XSS
    'samesite' => 'Strict',    // bloquea envío en requests cross-site → mitiga CSRF
]);

function adminShouldSkipAutoSessionStart(): bool {
    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($script !== 'login.php' || $method !== 'POST') {
        return false;
    }

    return empty($_COOKIE[session_name()]);
}

function adminEnsureSessionStarted(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
}

if (!adminShouldSkipAutoSessionStart()) {
    adminEnsureSessionStarted();
}

function adminCsrfToken(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }
    return (string)($_SESSION['admin_csrf_token'] ?? '');
}

function adminRequireCsrf(): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
        return;
    }

    $provided = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $expected = adminCsrfToken();
    if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
        jsonResponse(['error' => 'Invalid CSRF token'], 419);
    }
}
