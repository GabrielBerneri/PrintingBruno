<?php
/**
 * PrintingBruno - Cotización de envío por zonas
 * GET /api/shipping_quote.php?postal_code=5000
 *
 * Calcula el costo de envío según la zona del código postal.
 * Ajustá los precios en la sección CONFIGURACIÓN más abajo.
 *
 * Response: { "options": [{ "code": "GBA", "name": "Envío GBA", "price": 4500, "days": "3 a 5 días hábiles" }] }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

require_once __DIR__ . '/security/rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$rateKey = getRateLimitKey('shipping_quote');
checkAndIncrementRateLimit($rateKey, 60, 600, 600, 'Demasiadas solicitudes. Esperá unos minutos.');

$rawCP = preg_replace('/[^0-9]/', '', $_GET['postal_code'] ?? '');
if (strlen($rawCP) < 4 || strlen($rawCP) > 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Código postal inválido']);
    exit;
}

// ══════════════════════════════════════════════════════════
//  CONFIGURACIÓN DE ZONAS Y PRECIOS
//  Modificá los valores de "price" según lo que cobrás vos.
//  "days" es el texto de plazo estimado que ve el cliente.
// ══════════════════════════════════════════════════════════

$zonas = [
    'CABA' => [
        'name'  => 'Envío CABA',
        'price' => 3500,
        'days'  => '2 a 3 días hábiles',
    ],
    'GBA' => [
        'name'  => 'Envío GBA',
        'price' => 4500,
        'days'  => '3 a 5 días hábiles',
    ],
    'INTERIOR' => [
        'name'  => 'Envío Interior',
        'price' => 7000,
        'days'  => '5 a 8 días hábiles',
    ],
];

// ══════════════════════════════════════════════════════════
//  TABLA DE ZONAS POR CÓDIGO POSTAL
//  Rangos aproximados para Argentina (CP de 4 dígitos).
//  Podés agregar CPs específicos en $cpExactos si necesitás.
// ══════════════════════════════════════════════════════════

$cpNum = (int)$rawCP;

// CPs específicos que querés tratar diferente (opcional)
// Ejemplo: $cpExactos[1617] = 'GBA';
$cpExactos = [];

if (isset($cpExactos[$cpNum])) {
    $zona = $cpExactos[$cpNum];
} elseif ($cpNum >= 1000 && $cpNum <= 1499) {
    $zona = 'CABA';
} elseif (
    ($cpNum >= 1500 && $cpNum <= 1999) ||  // GBA Norte, Sur, Oeste
    ($cpNum >= 6000 && $cpNum <= 6999) ||  // Buenos Aires interior oeste
    ($cpNum >= 7000 && $cpNum <= 7999)     // Buenos Aires interior sur
) {
    $zona = 'GBA';
} else {
    $zona = 'INTERIOR';  // Todo el resto del país
}

// ══════════════════════════════════════════════════════════

$z = $zonas[$zona];
echo json_encode([
    'options' => [[
        'code'  => $zona,
        'name'  => $z['name'],
        'price' => $z['price'],
        'days'  => $z['days'],
    ]],
    'message' => null,
]);
