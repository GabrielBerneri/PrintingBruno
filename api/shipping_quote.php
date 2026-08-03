<?php
/**
 * PrintingBruno - Cotización de envío
 * GET /api/shipping_quote.php?postal_code=5000
 *
 * Consulta las tarifas públicas de Correo Argentino y retorna opciones
 * de envío con precio y tiempo estimado para el código postal de destino.
 *
 * Response: { "options": [{ "code": "EP", "name": "Encomienda Clásica", "price": 2800.50, "days": "3 a 5 días hábiles" }] }
 * Error:    { "options": [], "message": "..." }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

require_once __DIR__ . '/security/rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Limitar a 30 cotizaciones por IP cada 10 minutos
$rateKey = getRateLimitKey('shipping_quote');
checkAndIncrementRateLimit($rateKey, 30, 600, 600, 'Demasiadas solicitudes. Esperá unos minutos.');

// Código postal de destino (solo dígitos)
$destCP = preg_replace('/[^0-9]/', '', $_GET['postal_code'] ?? '');
if (strlen($destCP) < 4 || strlen($destCP) > 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Código postal inválido']);
    exit;
}

// ──────────────────────────────────────────────
// CONFIGURACIÓN
// ──────────────────────────────────────────────
$originCP   = '1425';   // CP desde donde se despacha
$weightKg   = 0.5;      // Peso por defecto del paquete en kg
$largoCm    = 20;       // Largo del paquete en cm
$anchoCm    = 15;       // Ancho del paquete en cm
$altoCm     = 10;       // Alto del paquete en cm

// Servicios a cotizar
$servicios = [
    ['id' => 1, 'code' => 'EP', 'name' => 'Encomienda Clásica'],
    ['id' => 4, 'code' => 'EU', 'name' => 'Encomienda Urgente'],
];
// ──────────────────────────────────────────────

$options = [];

foreach ($servicios as $servicio) {
    $result = caGetRate($originCP, $destCP, $servicio['id'], $weightKg, $largoCm, $anchoCm, $altoCm);

    if ($result === null) {
        continue;
    }

    // El API puede devolver el precio en diferentes campos según la versión
    $price = $result['precio']
        ?? $result['price']
        ?? $result['importe']
        ?? $result['total']
        ?? null;

    if ($price === null) {
        continue;
    }

    $days = $result['tiempoEntrega']
        ?? $result['plazo']
        ?? $result['diasEntrega']
        ?? '3 a 5 días hábiles';

    $options[] = [
        'code'  => $servicio['code'],
        'name'  => $servicio['name'],
        'price' => round((float)$price, 2),
        'days'  => (string)$days,
    ];
}

echo json_encode([
    'options' => $options,
    'message' => empty($options)
        ? 'No pudimos calcular el costo de envío. Te lo informaremos por WhatsApp.'
        : null,
]);


/**
 * Consulta la API pública de Correo Argentino para una tarifa.
 * Si la API no responde o cambia su formato, devuelve null (falla silenciosa).
 */
function caGetRate(string $origin, string $dest, int $productoId, float $peso, float $largo, float $ancho, float $alto): ?array
{
    // Endpoint público de Correo Argentino (sin registro ni API key)
    $url = 'https://api3.correoargentino.com.ar/mitelecorreo/v1/public/rates';

    $payload = json_encode([
        'codPostalOrigen'  => $origin,
        'codPostalDestino' => $dest,
        'productoId'       => $productoId,
        'peso'             => $peso,
        'largo'            => $largo,
        'ancho'            => $ancho,
        'alto'             => $alto,
    ]);

    if (!function_exists('curl_init')) {
        error_log('shipping_quote: cURL no disponible');
        return null;
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
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log("shipping_quote: cURL error (servicio=$productoId): $curlError");
        return null;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("shipping_quote: HTTP $httpCode (servicio=$productoId): $response");
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}
