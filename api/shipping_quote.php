<?php
/**
 * PrintingBruno - Cotización de envío por zonas
 * GET /api/shipping_quote.php?postal_code=5000
 *
 * Las zonas y precios se administran desde el panel de admin → Envíos.
 * Si no existe la tabla, devuelve fallback gracioso.
 *
 * Response: { "options": [{ "code": "GBA", "name": "Envío GBA", "price": 4500, "days": "3 a 5 días hábiles" }] }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

require_once __DIR__ . '/security/rate_limit.php';
require_once __DIR__ . '/db.php';

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

$cpNum = (int)$rawCP;

try {
    $db = getDB();

    // Si la tabla aún no existe, devolvemos fallback sin romper
    $tableExists = (bool)$db->query(
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'shipping_zones'"
    )->fetchColumn();

    if (!$tableExists) {
        echo json_encode(['options' => [], 'message' => 'El costo de envío se coordinará por WhatsApp.']);
        exit;
    }

    $zones = $db->query(
        "SELECT * FROM shipping_zones WHERE active = 1 ORDER BY sort_order ASC, id ASC"
    )->fetchAll();

    $matched = null;
    foreach ($zones as $zone) {
        $ranges = json_decode($zone['cp_ranges'] ?? '[]', true) ?: [];
        foreach ($ranges as $range) {
            if ($cpNum >= (int)$range['from'] && $cpNum <= (int)$range['to']) {
                $matched = $zone;
                break 2;
            }
        }
    }

    // Si el CP no cae en ningún rango definido, usamos la última zona (Interior)
    if ($matched === null && !empty($zones)) {
        $matched = end($zones);
    }

    if ($matched === null) {
        echo json_encode(['options' => [], 'message' => 'El costo de envío se coordinará por WhatsApp.']);
        exit;
    }

    echo json_encode([
        'options' => [[
            'code'  => $matched['code'],
            'name'  => $matched['name'],
            'price' => (float)$matched['price'],
            'days'  => $matched['days'],
        ]],
        'message' => null,
    ]);

} catch (Exception $e) {
    error_log('shipping_quote error: ' . $e->getMessage());
    echo json_encode(['options' => [], 'message' => 'El costo de envío se coordinará por WhatsApp.']);
}
