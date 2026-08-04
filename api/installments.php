<?php
/**
 * PrintingBruno - Cuotas disponibles para un monto
 * GET /api/installments.php?amount=5000
 *
 * Consulta MercadoPago para obtener las mejores cuotas disponibles.
 * Prioriza cuotas sin interés. Cachea 1 hora en el cliente.
 *
 * Response: { "installments": 3, "installment_amount": 1666.67, "free": true }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$amount = filter_input(INPUT_GET, 'amount', FILTER_VALIDATE_FLOAT);
if (!$amount || $amount < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Monto inválido']);
    exit;
}

// Redondear al 100 más cercano para maximizar cache hits
$amountRounded = max(100, round($amount / 100) * 100);

// Cache en disco por 1 hora
$cacheFile = sys_get_temp_dir() . '/pb_inst_' . (int)$amountRounded . '.json';
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 3600) {
    echo file_get_contents($cacheFile);
    exit;
}

$url = 'https://api.mercadopago.com/v1/payment_methods/installments?' . http_build_query([
    'access_token'      => MP_ACCESS_TOKEN,
    'amount'            => (string)$amountRounded,
    'payment_method_id' => 'visa',
    'bin'               => '450995',
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 5,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo consultar MercadoPago']);
    exit;
}

$data = json_decode($response, true);
if (!isset($data[0]['payer_costs'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Respuesta inesperada']);
    exit;
}

$bestFree = null;
$bestAny  = null;

foreach ($data[0]['payer_costs'] as $cost) {
    $n    = (int)$cost['installments'];
    if ($n < 2) continue;
    $rate = (float)($cost['installment_rate'] ?? 0);
    $out  = [
        'installments'       => $n,
        'installment_amount' => round((float)$cost['installment_amount'], 2),
        'free'               => ($rate === 0.0),
    ];
    if ($rate === 0.0 && (!$bestFree || $n > $bestFree['installments'])) {
        $bestFree = $out;
    }
    if (!$bestAny || $n > $bestAny['installments']) {
        $bestAny = $out;
    }
}

$result = $bestFree ?? $bestAny;
if (!$result) {
    http_response_code(404);
    echo json_encode(['error' => 'Sin cuotas disponibles']);
    exit;
}

$json = json_encode($result);
@file_put_contents($cacheFile, $json);
echo $json;
