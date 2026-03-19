<?php
/**
 * Creates or rotates an admin user password without shipping a default credential.
 *
 * Usage:
 *   php scripts/create_admin_user.php <username>
 *   PB_ADMIN_PASSWORD='super-secret-password' php scripts/create_admin_user.php <username>
 *   php scripts/create_admin_user.php <username> --password=super-secret-password
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once __DIR__ . '/../api/config.php';

function fail(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function usage(): void {
    fwrite(STDOUT, "Usage: php scripts/create_admin_user.php <username> [--password=VALUE]\n");
    fwrite(STDOUT, "Safer option: set PB_ADMIN_PASSWORD in the environment.\n");
}

$username = trim((string)($argv[1] ?? ''));
if ($username === '' || str_starts_with($username, '--')) {
    usage();
    exit(1);
}

if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
    fail('Invalid username. Use 3-50 chars: letters, numbers, dot, underscore or dash.');
}

$password = trim((string)pbEnv('PB_ADMIN_PASSWORD', ''));
foreach (array_slice($argv, 2) as $arg) {
    if (str_starts_with($arg, '--password=')) {
        $password = trim(substr($arg, 11));
    }
}

if ($password === '') {
    fwrite(STDOUT, "Password for {$username}: ");
    $password = trim((string)fgets(STDIN));
    fwrite(STDOUT, "Confirm password: ");
    $confirm = trim((string)fgets(STDIN));
    if ($password !== $confirm) {
        fail('Passwords do not match.');
    }
}

if (strlen($password) < 12) {
    fail('Password must be at least 12 characters.');
}

if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    fail('Password must include at least one letter and one number.');
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $stmt = $pdo->prepare('SELECT id FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $existing = $stmt->fetch();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($existing) {
        $update = $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
        $update->execute([$passwordHash, (int)$existing['id']]);
        fwrite(STDOUT, "Updated admin user: {$username}\n");
        exit(0);
    }

    $insert = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
    $insert->execute([$username, $passwordHash]);
    fwrite(STDOUT, "Created admin user: {$username}\n");
    exit(0);
} catch (Throwable $e) {
    fail('Failed to create admin user: ' . $e->getMessage());
}
