<?php
/**
 * PrintingBruno - Cuotas disponibles para un monto
 * GET /api/installments.php?amount=5000
 *
 * Devuelve todas las opciones de cuotas (cantidad, monto por cuota, total, si tiene interés).
 * Si la API de MP falla usa INSTALLMENTS_COUNT para calcular cuotas sin interés.
 *
 * Response: { "options": [{ "installments": 3, "installment_amount": 1667, "total_amount": 5000, "free": true }, ...] }
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

$amountRounded = max(100, round($amount / 100) * 100);

$cacheFile = sys_get_temp_dir() . '/pb_inst2_' . (int)$amountRounded . '.json';
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 3600) {
    echo file_get_contents($cacheFile);
    exit;
}

$options = tryMercadoPagoInstallments($amountRounded);

if (!$options) {
    // Fallback: cuotas sin interés según INSTALLMENTS_COUNT
    $options = buildLocalOptions($amountRounded);
}

$json = json_encode(['options' => $options]);
@file_put_contents($cacheFile, $json);
echo $json;

// ---------------------------------------------------------------------------

function tryMercadoPagoInstallments(float $amount): ?array
{
    if (!defined('MP_ACCESS_TOKEN') || str_starts_with(MP_ACCESS_TOKEN, 'TEST-0000')) {
        return null;
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

    if ($httpCode !== 200 || !$response) return null;

    $data = json_decode($response, true);
    if (!isset($data[0]['payer_costs']) || !is_array($data[0]['payer_costs'])) return null;

    $options = [];
    foreach ($data[0]['payer_costs'] as $cost) {
        $n = (int)$cost['installments'];
        if ($n < 1) continue;
        $options[] = [
            'installments'       => $n,
            'installment_amount' => round((float)$cost['installment_amount'], 2),
            'total_amount'       => round((float)$cost['total_amount'], 2),
            'free'               => ((float)($cost['installment_rate'] ?? 0)) === 0.0,
        ];
    }

    return count($options) > 0 ? $options : null;
}

function buildLocalOptions(float $amount): array
{
    $n = max(2, INSTALLMENTS_COUNT);
    $options = [
        ['installments' => 1, 'installment_amount' => $amount, 'total_amount' => $amount, 'free' => true],
    ];
    for ($i = 2; $i <= $n; $i++) {
        $options[] = [
            'installments'       => $i,
            'installment_amount' => round($amount / $i, 2),
            'total_amount'       => $amount,
            'free'               => true,
        ];
    }
    return $options;
}
