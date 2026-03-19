<?php
/**
 * Customer profile
 * GET /api/customer/profile.php
 * PUT /api/customer/profile.php
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../customer_auth.php';
require_once __DIR__ . '/../email/customer_verification.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $session = pbCustomerRequireAuth($db, false);
    jsonResponse(['customer' => $session['customer'], 'csrf_token' => $session['csrf_token']]);
}

if ($method !== 'PUT') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$session = pbCustomerRequireAuth($db, true);
$payload = getJsonBody();

[$firstName, $lastName] = pbCustomerNormalizeNameParts($payload + $session['customer']);
$email = trim((string)($payload['email'] ?? $session['customer']['email']));
$emailNormalized = pbCustomerNormalizeEmail($email);
$phone = trim((string)($payload['phone'] ?? $session['customer']['phone']));
$dni = trim((string)($payload['dni'] ?? $session['customer']['dni']));

if ($firstName === '') {
    jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
}
if ($emailNormalized === '' || !filter_var($emailNormalized, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Ingresá un email válido.'], 400);
}

$currentCustomer = pbCustomerFindByEmail($db, $session['customer']['email']);
if (!$currentCustomer) {
    jsonResponse(['error' => 'Cuenta no encontrada.'], 404);
}

$emailChanged = $emailNormalized !== pbCustomerNormalizeEmail($currentCustomer['email'] ?? '');
if ($emailChanged) {
    $existing = pbCustomerFindByEmail($db, $emailNormalized);
    if ($existing && (int)$existing['id'] !== (int)$session['customer_id']) {
        jsonResponse(['error' => 'Ese email ya está en uso por otra cuenta.'], 409);
    }
}

$db->beginTransaction();
try {
    $verifiedAt = $emailChanged ? null : ($currentCustomer['verified_at'] ?? null);
    $stmt = $db->prepare("
        UPDATE customers
        SET email = ?, email_normalized = ?, first_name = ?, last_name = ?, phone = ?, dni = ?, verified_at = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([
        $email,
        $emailNormalized,
        $firstName,
        $lastName !== '' ? $lastName : null,
        $phone !== '' ? $phone : null,
        $dni !== '' ? $dni : null,
        $verifiedAt,
        (int)$session['customer_id'],
    ]);

    $verificationToken = null;
    if ($emailChanged) {
        $verificationToken = pbCustomerCreateEmailVerification($db, (int)$session['customer_id']);
    }

    $db->commit();

    $updatedCustomer = pbCustomerFindByEmail($db, $emailNormalized);
    if ($emailChanged && $updatedCustomer && $verificationToken) {
        sendCustomerVerificationEmail($updatedCustomer, $verificationToken);
    }

    jsonResponse([
        'success' => true,
        'customer' => $updatedCustomer ? pbCustomerSanitizeProfile($updatedCustomer) : null,
        'verification_required' => $emailChanged,
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('customer/profile error: ' . $e->getMessage());
    jsonResponse(['error' => 'No se pudieron actualizar tus datos.'], 500);
}
