<?php
/**
 * Customer authentication, sessions, verification and recovery helpers.
 */

require_once __DIR__ . '/db.php';

function pbCustomerNormalizeEmail(?string $email): string {
    return strtolower(trim((string)$email));
}

function pbCustomerNormalizeNameParts(array $payload): array {
    $firstName = trim((string)($payload['first_name'] ?? ''));
    $lastName = trim((string)($payload['last_name'] ?? ''));

    if ($firstName === '') {
        $fullName = trim((string)($payload['name'] ?? ''));
        if ($fullName !== '') {
            $parts = preg_split('/\s+/', $fullName) ?: [];
            $firstName = array_shift($parts) ?: '';
            if ($lastName === '') {
                $lastName = trim(implode(' ', $parts));
            }
        }
    }

    return [$firstName, $lastName];
}

function pbCustomerFormatFullName(array $customer): string {
    $firstName = trim((string)($customer['first_name'] ?? ''));
    $lastName = trim((string)($customer['last_name'] ?? ''));
    return trim($firstName . ' ' . $lastName);
}

function pbCustomerSanitizeProfile(array $customer): array {
    return [
        'id' => (int)($customer['id'] ?? 0),
        'email' => (string)($customer['email'] ?? ''),
        'first_name' => trim((string)($customer['first_name'] ?? '')),
        'last_name' => trim((string)($customer['last_name'] ?? '')),
        'full_name' => pbCustomerFormatFullName($customer),
        'phone' => trim((string)($customer['phone'] ?? '')),
        'dni' => trim((string)($customer['dni'] ?? '')),
        'verified_at' => $customer['verified_at'] ?? null,
        'is_verified' => !empty($customer['verified_at']),
        'created_at' => $customer['created_at'] ?? null,
    ];
}

function pbCustomerPasswordValidationError(string $password): ?string {
    if (strlen($password) < 12) {
        return 'La contraseña debe tener al menos 12 caracteres.';
    }

    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'La contraseña debe incluir al menos una letra y un número.';
    }

    return null;
}

function pbCustomerSessionCookieOptions(): array {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443;

    return [
        'expires' => time() + CUSTOMER_SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ];
}

function pbCustomerSetSessionCookie(string $token): void {
    setcookie(CUSTOMER_SESSION_NAME, $token, pbCustomerSessionCookieOptions());
    $_COOKIE[CUSTOMER_SESSION_NAME] = $token;
}

function pbCustomerClearSessionCookie(): void {
    $options = pbCustomerSessionCookieOptions();
    $options['expires'] = time() - 3600;
    setcookie(CUSTOMER_SESSION_NAME, '', $options);
    unset($_COOKIE[CUSTOMER_SESSION_NAME]);
}

function pbCustomerTokenHash(string $token): string {
    return hash('sha256', $token);
}

function pbCustomerClientIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function pbCustomerCurrentToken(): string {
    return trim((string)($_COOKIE[CUSTOMER_SESSION_NAME] ?? ''));
}

function pbCustomerGetCurrentSession(PDO $db, bool $touch = true): ?array {
    $token = pbCustomerCurrentToken();
    if ($token === '') {
        return null;
    }

    $stmt = $db->prepare("
        SELECT
            cs.id AS session_id,
            cs.customer_id,
            cs.csrf_token,
            cs.expires_at,
            c.id,
            c.email,
            c.first_name,
            c.last_name,
            c.phone,
            c.dni,
            c.verified_at,
            c.created_at
        FROM customer_sessions cs
        INNER JOIN customers c ON c.id = cs.customer_id
        WHERE cs.token_hash = ?
          AND cs.expires_at >= NOW()
        LIMIT 1
    ");
    $stmt->execute([pbCustomerTokenHash($token)]);
    $row = $stmt->fetch();

    if (!$row) {
        pbCustomerClearSessionCookie();
        return null;
    }

    if ($touch) {
        $update = $db->prepare("
            UPDATE customer_sessions
            SET last_seen_at = NOW(),
                expires_at = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $update->execute([
            date('Y-m-d H:i:s', time() + CUSTOMER_SESSION_LIFETIME),
            (int)$row['session_id'],
        ]);
    }

    return [
        'session_id' => (int)$row['session_id'],
        'customer_id' => (int)$row['customer_id'],
        'csrf_token' => (string)$row['csrf_token'],
        'expires_at' => $row['expires_at'],
        'customer' => pbCustomerSanitizeProfile($row),
    ];
}

function pbCustomerRequireCsrf(array $session): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
        return;
    }

    $provided = trim((string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    $expected = trim((string)($session['csrf_token'] ?? ''));
    if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
        jsonResponse(['error' => 'Invalid CSRF token'], 419);
    }
}

function pbCustomerRequireAuth(PDO $db, bool $requireCsrf = false): array {
    $session = pbCustomerGetCurrentSession($db, true);
    if (!$session) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    if ($requireCsrf) {
        pbCustomerRequireCsrf($session);
    }

    return $session;
}

function pbCustomerCreateSession(PDO $db, int $customerId): array {
    $token = bin2hex(random_bytes(32));
    $csrfToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + CUSTOMER_SESSION_LIFETIME);

    $db->prepare('DELETE FROM customer_sessions WHERE customer_id = ?')->execute([$customerId]);
    $stmt = $db->prepare("
        INSERT INTO customer_sessions (customer_id, token_hash, csrf_token, expires_at, last_seen_at, ip_address, user_agent)
        VALUES (?, ?, ?, ?, NOW(), ?, ?)
    ");
    $stmt->execute([
        $customerId,
        pbCustomerTokenHash($token),
        $csrfToken,
        $expiresAt,
        pbCustomerClientIp(),
        substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);

    $db->prepare('UPDATE customers SET last_login_at = NOW() WHERE id = ?')->execute([$customerId]);
    pbCustomerSetSessionCookie($token);

    return [
        'token' => $token,
        'csrf_token' => $csrfToken,
        'expires_at' => $expiresAt,
    ];
}

function pbCustomerDestroyCurrentSession(PDO $db): void {
    $token = pbCustomerCurrentToken();
    if ($token !== '') {
        $stmt = $db->prepare('DELETE FROM customer_sessions WHERE token_hash = ?');
        $stmt->execute([pbCustomerTokenHash($token)]);
    }

    pbCustomerClearSessionCookie();
}

function pbCustomerFindByEmail(PDO $db, string $email): ?array {
    $normalized = pbCustomerNormalizeEmail($email);
    if ($normalized === '') {
        return null;
    }

    $stmt = $db->prepare('SELECT * FROM customers WHERE email_normalized = ? LIMIT 1');
    $stmt->execute([$normalized]);
    return $stmt->fetch() ?: null;
}

function pbCustomerCreateEmailVerification(PDO $db, int $customerId): string {
    $token = bin2hex(random_bytes(32));
    $stmt = $db->prepare('DELETE FROM customer_email_verifications WHERE customer_id = ?');
    $stmt->execute([$customerId]);

    $insert = $db->prepare("
        INSERT INTO customer_email_verifications (customer_id, token_hash, expires_at)
        VALUES (?, ?, ?)
    ");
    $insert->execute([
        $customerId,
        pbCustomerTokenHash($token),
        date('Y-m-d H:i:s', time() + CUSTOMER_EMAIL_VERIFICATION_TTL_SECONDS),
    ]);

    return $token;
}

function pbCustomerFindVerificationToken(PDO $db, string $token): ?array {
    if ($token === '') {
        return null;
    }

    $stmt = $db->prepare("
        SELECT
            cev.id AS verification_id,
            cev.customer_id,
            cev.expires_at,
            cev.verified_at AS token_verified_at,
            c.*
        FROM customer_email_verifications cev
        INNER JOIN customers c ON c.id = cev.customer_id
        WHERE cev.token_hash = ?
          AND cev.verified_at IS NULL
          AND cev.expires_at >= NOW()
        LIMIT 1
    ");
    $stmt->execute([pbCustomerTokenHash($token)]);
    return $stmt->fetch() ?: null;
}

function pbCustomerCreatePasswordReset(PDO $db, int $customerId): string {
    $token = bin2hex(random_bytes(32));
    $db->prepare('DELETE FROM customer_password_resets WHERE customer_id = ?')->execute([$customerId]);

    $stmt = $db->prepare("
        INSERT INTO customer_password_resets (customer_id, token_hash, expires_at, requested_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([
        $customerId,
        pbCustomerTokenHash($token),
        date('Y-m-d H:i:s', time() + CUSTOMER_PASSWORD_RESET_TTL_SECONDS),
    ]);

    return $token;
}

function pbCustomerFindPasswordResetToken(PDO $db, string $token): ?array {
    if ($token === '') {
        return null;
    }

    $stmt = $db->prepare("
        SELECT
            pr.id AS password_reset_id,
            pr.customer_id,
            pr.expires_at,
            pr.used_at,
            c.*
        FROM customer_password_resets pr
        INNER JOIN customers c ON c.id = pr.customer_id
        WHERE pr.token_hash = ?
          AND pr.used_at IS NULL
          AND pr.expires_at >= NOW()
        LIMIT 1
    ");
    $stmt->execute([pbCustomerTokenHash($token)]);
    return $stmt->fetch() ?: null;
}

function pbCustomerClaimGuestOrders(PDO $db, int $customerId, string $email): int {
    $normalized = pbCustomerNormalizeEmail($email);
    if ($customerId <= 0 || $normalized === '') {
        return 0;
    }

    $stmt = $db->prepare("
        UPDATE orders
        SET customer_id = ?
        WHERE customer_id IS NULL
          AND LOWER(TRIM(customer_email)) = ?
    ");
    $stmt->execute([$customerId, $normalized]);
    return $stmt->rowCount();
}
