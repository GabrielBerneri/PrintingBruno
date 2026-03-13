<?php
/**
 * PrintingBruno - Configuration
 * Database & MercadoPago credentials
 */

/**
 * Carga variables desde .env (root del proyecto) si existen.
 */
function pbLoadEnvFile(string $path): void {
    if (!is_file($path) || !is_readable($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;

        $key = trim($parts[0]);
        $val = trim($parts[1]);

        // Remover comillas envolventes
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }

        if ($key !== '') {
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

function pbEnv(string $key, ?string $default = null): ?string {
    $value = getenv($key);
    if ($value !== false) return $value;
    if (isset($_ENV[$key])) return (string)$_ENV[$key];
    if (isset($_SERVER[$key])) return (string)$_SERVER[$key];
    return $default;
}

pbLoadEnvFile(__DIR__ . '/../.env');

// ===== Database Configuration =====
// LOCAL XAMPP — para producción usar credenciales de Hostinger
define('DB_HOST', pbEnv('DB_HOST', 'localhost'));
define('DB_NAME', pbEnv('DB_NAME', 'printingbruno'));
define('DB_USER', pbEnv('DB_USER', 'root'));
define('DB_PASS', pbEnv('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// ===== MercadoPago Configuration =====
// Get your credentials from: https://www.mercadopago.com.ar/developers/panel/app
define('MP_ACCESS_TOKEN', pbEnv('MP_ACCESS_TOKEN', 'TEST-0000000000000000-000000-00000000000000000000000000000000-000000000')); // Replace with your access token
define('MP_PUBLIC_KEY', pbEnv('MP_PUBLIC_KEY', 'TEST-00000000-0000-0000-0000-000000000000')); // Replace with your public key
// Webhook secret: Configuraciones > Webhooks > Clave secreta en el panel de MP
define('MP_WEBHOOK_SECRET', pbEnv('MP_WEBHOOK_SECRET', 'your_webhook_secret_here')); // Replace with your webhook secret

// ===== Site Configuration =====
// ⚠️  PRODUCCIÓN: cambiar al dominio real (con https://)
define('SITE_URL', pbEnv('SITE_URL', 'https://localhost/printingbruno')); // LOCAL — en producción usar dominio real
define('SITE_NAME', pbEnv('SITE_NAME', 'PrintingBruno'));

// ===== MercadoPago Redirect URLs =====
define('MP_SUCCESS_URL', SITE_URL . '/checkout-success.html');
define('MP_FAILURE_URL', SITE_URL . '/checkout-failure.html');
define('MP_PENDING_URL', SITE_URL . '/checkout-pending.html');
define('MP_WEBHOOK_URL', SITE_URL . '/api/webhook.php');

// ===== Admin Session =====
define('ADMIN_SESSION_NAME', 'pb_admin_session');
define('ADMIN_SESSION_LIFETIME', 3600 * 8); // 8 hours

// ===== Email SMTP Configuration (Hostinger) =====
define('SMTP_HOST', pbEnv('SMTP_HOST', 'smtp.hostinger.com'));
define('SMTP_PORT', (int)pbEnv('SMTP_PORT', '465'));
define('SMTP_USER', pbEnv('SMTP_USER', 'contacto@printingbruno.com'));
define('SMTP_PASS', pbEnv('SMTP_PASS', 'INGprg2706!'));
define('SMTP_FROM_NAME', pbEnv('SMTP_FROM_NAME', 'PrintingBruno'));

// ===== CORS Headers (for API) =====
header('Content-Type: application/json; charset=utf-8');
// Restringir CORS al dominio propio — nunca abierto a *
$allowedOrigin = rtrim(SITE_URL, '/');
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($requestOrigin === $allowedOrigin) {
    header("Access-Control-Allow-Origin: $allowedOrigin");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
}
// Si el origin no coincide, NO enviar headers CORS → bloqueo por navegador

// Handle preflight
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error reporting — NUNCA mostrar errores en producción (info sensible)
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
