<?php
/**
 * TEMPORAL - Diagnóstico de cotización de envío
 * GET /api/shipping_debug.php
 * Borrar después de usarlo.
 */
header('Content-Type: application/json; charset=utf-8');

$originCP = '1425';
$destCP   = '1617';
$url = 'https://api3.correoargentino.com.ar/mitelecorreo/v1/public/rates';

$payload = json_encode([
    'codPostalOrigen'  => $originCP,
    'codPostalDestino' => $destCP,
    'productoId'       => 1,
    'peso'             => 0.5,
    'largo'            => 20,
    'ancho'            => 15,
    'alto'             => 10,
]);

$result = [
    'curl_available' => function_exists('curl_init'),
    'php_version'    => PHP_VERSION,
    'url'            => $url,
    'payload'        => json_decode($payload),
    'http_code'      => null,
    'curl_error'     => null,
    'response_raw'   => null,
    'response_json'  => null,
];

if (!function_exists('curl_init')) {
    echo json_encode($result);
    exit;
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: PrintingBruno/1.0',
    ],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$result['http_code']    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$result['curl_error']   = curl_error($ch) ?: null;
$result['response_raw'] = $response === false ? 'curl_exec returned false' : substr($response, 0, 2000);
$result['response_json'] = $response ? json_decode($response) : null;
curl_close($ch);

// Intentar sin SSL_VERIFYPEER si falló
if ($response === false || $result['http_code'] === 0) {
    $ch2 = curl_init($url);
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $r2 = curl_exec($ch2);
    $result['retry_no_ssl'] = [
        'http_code'    => curl_getinfo($ch2, CURLINFO_HTTP_CODE),
        'curl_error'   => curl_error($ch2) ?: null,
        'response_raw' => $r2 === false ? 'curl_exec returned false' : substr((string)$r2, 0, 2000),
    ];
    curl_close($ch2);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
