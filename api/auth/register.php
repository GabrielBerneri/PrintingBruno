<?php
/**
 * Customer registration
 * POST /api/auth/register.php
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../customer_auth.php';
require_once __DIR__ . '/../security/rate_limit.php';
require_once __DIR__ . '/../email/customer_verification.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

checkAndIncrementRateLimit(
    getRateLimitKey('customer_register'),
    5,
    3600,
    3600,
    'Demasiados intentos de registro. Intentá nuevamente más tarde.'
);

$payload = getJsonBody();
[$firstName, $lastName] = pbCustomerNormalizeNameParts($payload);
$email = trim((string)($payload['email'] ?? ''));
$emailNormalized = pbCustomerNormalizeEmail($email);
$password = (string)($payload['password'] ?? '');
$passwordConfirm = (string)($payload['password_confirm'] ?? '');
$phone = trim((string)($payload['phone'] ?? ''));
$dni = trim((string)($payload['dni'] ?? ''));

if ($firstName === '' || $emailNormalized === '' || $password === '') {
    jsonResponse(['error' => 'Nombre, email y contraseña son obligatorios.'], 400);
}

if (!filter_var($emailNormalized, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Email inválido.'], 400);
}

if ($passwordConfirm !== '' && $password !== $passwordConfirm) {
    jsonResponse(['error' => 'Las contraseñas no coinciden.'], 400);
}

$passwordError = pbCustomerPasswordValidationError($password);
if ($passwordError !== null) {
    jsonResponse(['error' => $passwordError], 400);
}

$db = getDB();
if (pbCustomerFindByEmail($db, $emailNormalized)) {
    jsonResponse(['error' => 'Ya existe una cuenta con ese email.'], 409);
}

$db->beginTransaction();
try {
    $stmt = $db->prepare("
        INSERT INTO customers (email, email_normalized, first_name, last_name, phone, dni, password_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $email,
        $emailNormalized,
        $firstName,
        $lastName !== '' ? $lastName : null,
        $phone !== '' ? $phone : null,
        $dni !== '' ? $dni : null,
        password_hash($password, PASSWORD_DEFAULT),
    ]);
    $customerId = (int)$db->lastInsertId();
    $verificationToken = pbCustomerCreateEmailVerification($db, $customerId);
    $db->commit();

    $sessionData = pbCustomerCreateSession($db, $customerId);
    $customer = pbCustomerFindByEmail($db, $emailNormalized);
    if ($customer) {
        sendCustomerVerificationEmail($customer, $verificationToken);
    }

    jsonResponse([
        'success' => true,
        'customer' => $customer ? pbCustomerSanitizeProfile($customer) : null,
        'csrf_token' => $sessionData['csrf_token'],
        'verification_required' => true,
    ], 201);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('auth/register error: ' . $e->getMessage());
    jsonResponse(['error' => 'No se pudo crear la cuenta.'], 500);
}
