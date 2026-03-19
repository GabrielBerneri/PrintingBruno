<?php
/**
 * Customer login/logout
 * POST   /api/auth/login.php
 * DELETE /api/auth/login.php
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../customer_auth.php';
require_once __DIR__ . '/../security/rate_limit.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    checkRateLimit(getRateLimitKey('customer_login'));

    $payload = getJsonBody();
    $email = pbCustomerNormalizeEmail($payload['email'] ?? '');
    $password = (string)($payload['password'] ?? '');

    if ($email === '' || $password === '') {
        jsonResponse(['error' => 'Email y contraseña son obligatorios.'], 400);
    }

    $customer = pbCustomerFindByEmail($db, $email);
    if (!$customer || !password_verify($password, (string)$customer['password_hash'])) {
        recordFailedAttempt(getRateLimitKey('customer_login'));
        jsonResponse(['error' => 'Credenciales inválidas.'], 401);
    }

    clearRateLimit(getRateLimitKey('customer_login'));
    $sessionData = pbCustomerCreateSession($db, (int)$customer['id']);

    jsonResponse([
        'success' => true,
        'customer' => pbCustomerSanitizeProfile($customer),
        'csrf_token' => $sessionData['csrf_token'],
        'verification_required' => empty($customer['verified_at']),
    ]);
}

if ($method === 'DELETE') {
    $session = pbCustomerRequireAuth($db, true);
    pbCustomerDestroyCurrentSession($db);
    jsonResponse(['success' => true, 'customer_id' => $session['customer_id']]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
