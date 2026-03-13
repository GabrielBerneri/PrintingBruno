<?php
/**
 * PrintingBruno - Admin API: Login
 * POST   /api/admin/login.php  → Iniciar sesión
 * GET    /api/admin/login.php  → Verificar sesión activa
 * DELETE /api/admin/login.php  → Cerrar sesión
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/rate_limit.php';
require_once __DIR__ . '/session.php';  // session segura (HttpOnly, Secure, SameSite)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rateLimitKey = getRateLimitKey('admin_login');
    checkRateLimit($rateLimitKey);  // bloquea si superó el límite

    $body     = getJsonBody();
    $username = trim($body['username'] ?? '');
    $password = $body['password'] ?? '';

    if (empty($username) || empty($password)) {
        jsonResponse(['error' => 'Usuario y contraseña requeridos'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Login exitoso — regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);
        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_user'] = $user['username'];
        clearRateLimit($rateLimitKey);
        jsonResponse(['success' => true, 'user' => $user['username']]);
    } else {
        recordFailedAttempt($rateLimitKey);
        // Mismo mensaje para usuario y contraseña → no revela cuál es incorrecto
        jsonResponse(['error' => 'Credenciales inválidas'], 401);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (!empty($_SESSION['admin_id'])) {
        jsonResponse(['authenticated' => true, 'user' => $_SESSION['admin_user']]);
    } else {
        jsonResponse(['authenticated' => false], 401);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    // Destruir sesión completamente y limpiar cookie
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    jsonResponse(['success' => true]);
}
