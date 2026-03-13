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

/**
 * Verifica si la IP está bloqueada. Si lo está, devuelve error 429 y detiene ejecución.
 */
function checkRateLimit(string $key): void {
    $file = getRateLimitFile($key);
    $now  = time();

    if (!file_exists($file)) return;

    $data = json_decode(file_get_contents($file), true);
    if (!$data) return;

    // Lockout activo
    if (!empty($data['locked_until']) && $data['locked_until'] > $now) {
        $minutes = (int) ceil(($data['locked_until'] - $now) / 60);
        jsonResponse([
            'error' => "Demasiados intentos fallidos. Intente de nuevo en $minutes minuto(s)."
        ], 429);
    }
}

/**
 * Registra un intento fallido. Si alcanza el límite, activa el lockout.
 */
function recordFailedAttempt(string $key): void {
    $file = getRateLimitFile($key);
    $now  = time();

    $data = ['attempts' => [], 'locked_until' => 0];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?? $data;
    }

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
