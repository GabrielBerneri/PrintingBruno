<?php
/**
 * Customer email verification
 * GET /api/auth/verify_email.php?token=...
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../customer_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '') {
    jsonResponse(['error' => 'Falta el token de verificación.'], 400);
}

$db = getDB();
$verification = pbCustomerFindVerificationToken($db, $token);
if (!$verification) {
    jsonResponse(['success' => false, 'error' => 'El enlace es inválido o ya venció.'], 400);
}

$db->beginTransaction();
try {
    $db->prepare('UPDATE customers SET verified_at = NOW() WHERE id = ?')->execute([(int)$verification['customer_id']]);
    $db->prepare('UPDATE customer_email_verifications SET verified_at = NOW() WHERE id = ?')->execute([(int)$verification['verification_id']]);
    $claimedOrders = pbCustomerClaimGuestOrders($db, (int)$verification['customer_id'], (string)$verification['email']);
    $db->commit();

    jsonResponse([
        'success' => true,
        'message' => 'Tu cuenta quedó verificada.',
        'claimed_orders' => $claimedOrders,
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('auth/verify_email error: ' . $e->getMessage());
    jsonResponse(['error' => 'No se pudo verificar la cuenta.'], 500);
}
