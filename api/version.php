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
require_once __DIR__ . '/version_helpers.php';

// Determinar si el acceso está autorizado para ver detalles
$providedToken   = $_SERVER['HTTP_X_VERSION_TOKEN'] ?? '';
$isLocalRequest  = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$isTokenValid    = VERSION_TOKEN !== '' && hash_equals(VERSION_TOKEN, $providedToken);
$isAuthorized    = $isLocalRequest || $isTokenValid;

// Respuesta pública mínima (sin información sensible)
$versionInfo = pbGetVersionInfo();
$response = [
    'app'    => $versionInfo['app'],
    'status' => 'ok',
];

// Detalle extendido solo para accesos autorizados
if ($isAuthorized) {
    $response['environment'] = APP_ENV;
    $response['version'] = $versionInfo['version'];
    $response['asset_version'] = $versionInfo['asset_version'];
    $response['fingerprint'] = $versionInfo['fingerprint'];
    $response['commit'] = $versionInfo['commit'];
    $response['branch'] = $versionInfo['branch'];
    $response['built_at'] = $versionInfo['built_at'];
    $response['deployed_at'] = $versionInfo['deployed_at'];
}

http_response_code(200);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
