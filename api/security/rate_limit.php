<?php
/**
 * PrintingBruno - Rate Limiter (file-based, compatible con shared hosting)
 * Protege endpoints contra fuerza bruta por IP.
 *
 * Uso:
 *   checkRateLimit(getRateLimitKey('admin_login'));
 *   recordFailedAttempt(getRateLimitKey('admin_login'));
 *   clearRateLimit(getRateLimitKey('admin_login'));
 */

define('RATE_LIMIT_MAX_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW_SECONDS', 900);   // 15 minutos
define('RATE_LIMIT_LOCKOUT_SECONDS', 900);  // 15 minutos de bloqueo

/**
 * Obtiene la IP real del cliente.
 * Usa REMOTE_ADDR directamente — X-Forwarded-For es manipulable por el cliente
 * y solo debe confiarse detrás de un proxy conocido.
 */
function getClientIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Genera la clave de rate limit para un contexto + IP.
 */
function getRateLimitKey(string $context): string {
    return $context . '_' . md5(getClientIp());
}

/**
 * Devuelve la ruta al archivo de estado de rate limit.
 * Usa sys_get_temp_dir() — siempre escribible, nunca accesible vía web.
 */
function getRateLimitFile(string $key): string {
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pb_rl_' . md5($key) . '.json';
}

function loadRateLimitState(string $file): array {
    $default = ['attempts' => [], 'locked_until' => 0];

    if (!is_file($file) || !is_readable($file)) {
        return $default;
    }

    $raw = file_get_contents($file);
    if (!is_string($raw) || trim($raw) === '') {
        return $default;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return $default;
    }

    $attempts = $data['attempts'] ?? [];
    if (!is_array($attempts)) {
        $attempts = [];
    }

    $attempts = array_values(array_filter(array_map(static function ($value) {
        return (int)$value;
    }, $attempts), static function (int $value) {
        return $value > 0;
    }));

    return [
        'attempts' => $attempts,
        'locked_until' => max(0, (int)($data['locked_until'] ?? 0)),
    ];
}

/**
 * Verifica si la IP está bloqueada. Si lo está, devuelve error 429 y detiene ejecución.
 */
function checkRateLimit(string $key): void {
    $file = getRateLimitFile($key);
    $now  = time();

    if (!file_exists($file)) return;

    $data = loadRateLimitState($file);

    // Lockout activo
    if (!empty($data['locked_until']) && $data['locked_until'] > $now) {
        $minutes = (int) ceil(($data['locked_until'] - $now) / 60);
        jsonResponse([
            'error' => "Demasiados intentos fallidos. Intente de nuevo en $minutes minuto(s)."
        ], 429);
    }
}

/**
 * Verifica y registra un intento en una sola operación.
 * Útil para endpoints costosos donde cada request debe contar.
 */
function checkAndIncrementRateLimit(
    string $key,
    int $maxAttempts = RATE_LIMIT_MAX_ATTEMPTS,
    int $windowSeconds = RATE_LIMIT_WINDOW_SECONDS,
    int $lockoutSeconds = RATE_LIMIT_LOCKOUT_SECONDS,
    ?string $errorMessage = null
): void {
    $file = getRateLimitFile($key);
    $now  = time();

    $data = loadRateLimitState($file);

    if (!empty($data['locked_until']) && $data['locked_until'] > $now) {
        $minutes = (int) ceil(($data['locked_until'] - $now) / 60);
        jsonResponse([
            'error' => $errorMessage ?: "Demasiados intentos fallidos. Intente de nuevo en $minutes minuto(s)."
        ], 429);
    }

    $data['attempts'] = array_values(
        array_filter($data['attempts'], fn($t) => $t > $now - $windowSeconds)
    );

    if (count($data['attempts']) >= $maxAttempts) {
        $data['locked_until'] = $now + $lockoutSeconds;
        $data['attempts'] = [];
        file_put_contents($file, json_encode($data), LOCK_EX);
        jsonResponse([
            'error' => $errorMessage ?: 'Demasiados intentos. Intente nuevamente más tarde.'
        ], 429);
    }

    $data['attempts'][] = $now;
    file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Registra un intento fallido. Si alcanza el límite, activa el lockout.
 */
function recordFailedAttempt(string $key): void {
    $file = getRateLimitFile($key);
    $now  = time();

    $data = loadRateLimitState($file);

    // Descartar intentos fuera de la ventana de tiempo
    $data['attempts'] = array_values(
        array_filter($data['attempts'], fn($t) => $t > $now - RATE_LIMIT_WINDOW_SECONDS)
    );

    $data['attempts'][] = $now;

    if (count($data['attempts']) >= RATE_LIMIT_MAX_ATTEMPTS) {
        $data['locked_until'] = $now + RATE_LIMIT_LOCKOUT_SECONDS;
        $data['attempts']     = [];
    }

    file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Limpia el contador de intentos tras un login exitoso.
 */
function clearRateLimit(string $key): void {
    $file = getRateLimitFile($key);
    if (file_exists($file)) {
        unlink($file);
    }
}
