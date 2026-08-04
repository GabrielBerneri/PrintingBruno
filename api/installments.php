<?php
/**
 * PrintingBruno - Cuota para INSTALLMENTS_COUNT cuotas
 * GET /api/installments.php?amount=5000
 *
 * Devuelve el monto por cuota para la cantidad configurada en INSTALLMENTS_COUNT.
 * Usa la API de MP si está disponible (monto real con interés incluido).
 * Fallback: divide el monto por INSTALLMENTS_COUNT.
 *
 * Response: { "installments": 3, "installment_amount": 1800.50 }
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

$n             = max(2, INSTALLMENTS_COUNT);
$amountRounded = max(100, round($amount / 100) * 100);

$cacheFile = sys_get_temp_dir() . '/pb_inst3_' . $n . '_' . (int)$amountRounded . '.json';
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 3600) {
    echo file_get_contents($cacheFile);
    exit;
}

$perCuota = getInstallmentAmount($amountRounded, $n);

$result = ['installments' => $n, 'installment_amount' => $perCuota];
$json   = json_encode($result);
@file_put_contents($cacheFile, $json);
echo $json;

// ---------------------------------------------------------------------------

function getInstallmentAmount(float $amount, int $n): float
{
    if (!defined('MP_ACCESS_TOKEN') || str_starts_with(MP_ACCESS_TOKEN, 'TEST-0000')) {
        return round($amount / $n, 2);
    }

    $url = 'https://api.mercadopago.com/v1/payment_methods/installments?' . http_build_query([
        'access_token'      => MP_ACCESS_TOKEN,
        'amount'            => (string)$amount,
        'payment_method_id' => 'visa',
        'bin'               => '450995',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return round($amount / $n, 2);

    $data = json_decode($response, true);
    if (!isset($data[0]['payer_costs'])) return round($amount / $n, 2);

    foreach ($data[0]['payer_costs'] as $cost) {
        if ((int)$cost['installments'] === $n) {
            return round((float)$cost['installment_amount'], 2);
        }
    }

    return round($amount / $n, 2);
}
