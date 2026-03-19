<?php
/**
 * Applies a SQL migration file using project DB credentials.
 *
 * Usage:
 *   php scripts/apply_migration.php sql/migrations/20260315_checkout_stock_security.sql
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$migrationPath = $argv[1] ?? '';
if ($migrationPath === '') {
    fwrite(STDERR, "Missing migration path.\n");
    exit(1);
}

$fullPath = realpath(__DIR__ . '/../' . ltrim(str_replace('\\', '/', $migrationPath), '/'));
if ($fullPath === false || !is_file($fullPath)) {
    fwrite(STDERR, "Migration file not found: {$migrationPath}\n");
    exit(1);
}

require_once __DIR__ . '/../api/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $sql = (string)file_get_contents($fullPath);
    $pdo->exec($sql);
    fwrite(STDOUT, "Applied migration: {$migrationPath}\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Migration error: " . $e->getMessage() . "\n");
    exit(1);
}
