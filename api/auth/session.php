<?php
/**
 * Customer session status
 * GET /api/auth/session.php
 */

require_once __DIR__ . '/../customer_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$db = getDB();
$session = pbCustomerGetCurrentSession($db, true);

if (!$session) {
    jsonResponse(['authenticated' => false]);
}

jsonResponse([
    'authenticated' => true,
    'customer' => $session['customer'],
    'csrf_token' => $session['csrf_token'],
    'expires_at' => $session['expires_at'],
]);
