<?php
/**
 * PrintingBruno - Cuotas disponibles para un monto
 * GET /api/installments.php?amount=5000
 *
 * Intenta obtener cuotas reales de la API de MercadoPago.
 * Si falla (modo test, sin conectividad), calcula con INSTALLMENTS_COUNT.
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

$amountRounded = max(100, round($amount / 100) * 100);

// Cache en disco 1 hora
$cacheFile = sys_get_temp_dir() . '/pb_inst_' . (int)$amountRounded . '.json';
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 3600) {
    echo file_get_contents($cacheFile);
    exit;
}

// --- Intenta API de MercadoPago ---
$result = tryMercadoPagoInstallments($amountRounded);

// --- Fallback: cálculo local con INSTALLMENTS_COUNT ---
if (!$result) {
    $n = max(2, INSTALLMENTS_COUNT);
    $result = [
        'installments'       => $n,
        'installment_amount' => round($amountRounded / $n, 2),
        'free'               => true,
    ];
}

$json = json_encode($result);
@file_put_contents($cacheFile, $json);
echo $json;

// ---------------------------------------------------------------------------

function tryMercadoPagoInstallments(float $amount): ?array
{
    if (!defined('MP_ACCESS_TOKEN') || str_starts_with(MP_ACCESS_TOKEN, 'TEST-0000')) {
        return null; // credenciales de placeholder, saltar
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

    if ($httpCode !== 200 || !$response) {
        return null;
    }

    $data = json_decode($response, true);
    if (!isset($data[0]['payer_costs']) || !is_array($data[0]['payer_costs'])) {
        return null;
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

    return $bestFree ?? $bestAny;
}
