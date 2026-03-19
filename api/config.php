<?php
/**
 * PrintingBruno - Configuration
 * Database & MercadoPago credentials
 */

/**
 * Carga variables desde un archivo .env si existe.
 * No sobreescribe variables ya definidas por el entorno/proceso.
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
            $alreadySet = getenv($key) !== false || array_key_exists($key, $_ENV) || array_key_exists($key, $_SERVER);
            if ($alreadySet) {
                continue;
            }

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

function pbPreferProjectEnv(string $projectRoot): bool {
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    if ($host !== '' && preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $host) === 1) {
        return true;
    }

    if (PHP_SAPI === 'cli' && preg_match('/^[a-z]:\\\\/i', $projectRoot) === 1) {
        return true;
    }

    return false;
}

function pbBootstrapEnv(): void {
    $projectRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
    $sharedRoot = dirname($projectRoot);
    $paths = [
        $sharedRoot . DIRECTORY_SEPARATOR . '.env',
        $projectRoot . DIRECTORY_SEPARATOR . '.env',
    ];

    // Localhost/XAMPP: el .env del proyecto manda. Producción: el .env fuera del webroot manda.
    if (pbPreferProjectEnv($projectRoot)) {
        $paths = array_reverse($paths);
    }

    foreach ($paths as $path) {
        pbLoadEnvFile($path);
    }
}

pbBootstrapEnv();

function pbIsApiRequest(): bool {
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
    return preg_match('#/api(?:/|$)#', $scriptName) === 1;
}

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
define('APP_ENV', pbEnv('APP_ENV', 'production'));
define('VERSION_TOKEN', pbEnv('VERSION_TOKEN', ''));
define('ORDER_ACCESS_SECRET', pbEnv('ORDER_ACCESS_SECRET', VERSION_TOKEN));
define('GA4_MEASUREMENT_ID', trim((string)pbEnv('GA4_MEASUREMENT_ID', '')));

// ===== MercadoPago Redirect URLs =====
define('MP_SUCCESS_URL', SITE_URL . '/checkout-success.html');
define('MP_FAILURE_URL', SITE_URL . '/checkout-failure.html');
define('MP_PENDING_URL', SITE_URL . '/checkout-pending.html');
define('MP_WEBHOOK_URL', SITE_URL . '/api/webhook.php');

// ===== Admin Session =====
define('ADMIN_SESSION_NAME', 'pb_admin_session');
define('ADMIN_SESSION_LIFETIME', 3600 * 8); // 8 hours
define('CUSTOMER_SESSION_NAME', 'pb_customer_session');
define('CUSTOMER_SESSION_LIFETIME', max(3600, (int)pbEnv('CUSTOMER_SESSION_LIFETIME', (string)(3600 * 24 * 30))));
define('CUSTOMER_PASSWORD_RESET_TTL_SECONDS', max(900, (int)pbEnv('CUSTOMER_PASSWORD_RESET_TTL_SECONDS', '7200')));
define('CUSTOMER_EMAIL_VERIFICATION_TTL_SECONDS', max(3600, (int)pbEnv('CUSTOMER_EMAIL_VERIFICATION_TTL_SECONDS', (string)(3600 * 24 * 3))));

// ===== Email SMTP Configuration (Hostinger) =====
define('SMTP_HOST', pbEnv('SMTP_HOST', 'smtp.hostinger.com'));
define('SMTP_PORT', (int)pbEnv('SMTP_PORT', '465'));
define('SMTP_USER', pbEnv('SMTP_USER', 'contacto@printingbruno.com'));
define('SMTP_PASS', pbEnv('SMTP_PASS', ''));
define('SMTP_FROM_NAME', pbEnv('SMTP_FROM_NAME', 'PrintingBruno'));

// ===== CORS Headers (solo API) =====
if (pbIsApiRequest()) {
    header('Content-Type: application/json; charset=utf-8');
    // Restringir CORS al dominio propio — acepta www y sin www
    $baseUrl = rtrim(SITE_URL, '/');
    $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
    // Generar ambas variantes (con y sin www) para máxima compatibilidad
    $allowedOrigins = [$baseUrl];
    if (strpos($baseUrl, '://www.') !== false) {
        $allowedOrigins[] = str_replace('://www.', '://', $baseUrl);
    } else {
        $allowedOrigins[] = str_replace('://', '://www.', $baseUrl);
    }
    if (in_array($requestOrigin, $allowedOrigins, true)) {
        header("Access-Control-Allow-Origin: $requestOrigin");
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Csrf-Token');
        header('Access-Control-Allow-Credentials: true');
    }
    // Si el origin no coincide, NO enviar headers CORS → bloqueo por navegador

    // Handle preflight
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Error reporting — NUNCA mostrar errores en producción (info sensible)
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
