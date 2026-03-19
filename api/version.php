<?php
/**
 * Endpoint de versión de despliegue
 * GET /api/version.php
 *
 * En producción solo expone el campo 'app' y 'status'.
 * Los detalles internos (commit, branch, env) solo se muestran
 * cuando la IP coincide con la del servidor local o se pasa el
 * header X-Version-Token configurado fuera del webroot.
 */

require_once __DIR__ . '/config.php';

// Determinar si el acceso está autorizado para ver detalles
$providedToken   = $_SERVER['HTTP_X_VERSION_TOKEN'] ?? '';
$isLocalRequest  = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$isTokenValid    = VERSION_TOKEN !== '' && hash_equals(VERSION_TOKEN, $providedToken);
$isAuthorized    = $isLocalRequest || $isTokenValid;

// Respuesta pública mínima (sin información sensible)
$response = [
    'app'    => 'printingbruno',
    'status' => 'ok',
];

// Detalle extendido solo para accesos autorizados
if ($isAuthorized) {
    $versionFile = __DIR__ . '/../version.json';
    $fromFile = [];
    if (is_file($versionFile) && is_readable($versionFile)) {
        $json = json_decode((string)file_get_contents($versionFile), true);
        if (is_array($json)) $fromFile = $json;
    }

    $response['environment']  = APP_ENV;
    $response['version']      = pbEnv('APP_VERSION', $fromFile['version'] ?? 'unknown');
    $response['commit']       = pbEnv('GIT_COMMIT',  $fromFile['commit']  ?? 'unknown');
    $response['branch']       = pbEnv('GIT_BRANCH',  $fromFile['branch']  ?? 'unknown');
    $response['deployed_at']  = pbEnv('DEPLOYED_AT', $fromFile['deployed_at'] ?? null);
}

http_response_code(200);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
