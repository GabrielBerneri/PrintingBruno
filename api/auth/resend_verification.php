<?php
/**
 * Customer resend verification email
 * POST /api/auth/resend_verification.php
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../customer_auth.php';
require_once __DIR__ . '/../security/rate_limit.php';
require_once __DIR__ . '/../email/customer_verification.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$db = getDB();
$session = pbCustomerRequireAuth($db, true);
$customer = $session['customer'] ?? null;
$customerId = (int)($session['customer_id'] ?? 0);

if (!$customer || $customerId <= 0) {
    jsonResponse(['error' => 'Cuenta no encontrada.'], 404);
}

if (!empty($customer['is_verified'])) {
    jsonResponse([
        'success' => true,
        'already_verified' => true,
        'message' => 'Tu cuenta ya está verificada.',
        'customer' => $customer,
    ]);
}

checkAndIncrementRateLimit(
    getRateLimitKey('customer_resend_verification_' . $customerId),
    3,
    3600,
    3600,
    'Demasiadas solicitudes de verificación. Intentá nuevamente en una hora.'
);

try {
    $token = pbCustomerCreateEmailVerification($db, $customerId);
    $sent = sendCustomerVerificationEmail($customer, $token);
    if (!$sent) {
        jsonResponse(['error' => 'No se pudo reenviar el email de verificación.'], 500);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Te reenviamos el email de verificación.',
        'customer' => $customer,
    ]);
} catch (Throwable $e) {
    error_log('auth/resend_verification error: ' . $e->getMessage());
    jsonResponse(['error' => 'No se pudo reenviar el email de verificación.'], 500);
}
