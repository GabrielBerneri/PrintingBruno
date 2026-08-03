<?php
/**
 * PrintingBruno - Admin API: Zonas de envío
 * GET /api/admin/shipping_zones.php         → Listar zonas
 * PUT /api/admin/shipping_zones.php?id=X    → Actualizar precio/plazo/nombre
 *
 * Auto-crea la tabla y pre-carga las zonas argentinas si no existen.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db.php';

if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

adminRequireCsrf();

$db = getDB();

function pbEnsureShippingZones(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS `shipping_zones` (
        `id`          INT              NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `code`        VARCHAR(30)      NOT NULL,
        `name`        VARCHAR(100)     NOT NULL,
        `description` VARCHAR(255)     DEFAULT NULL,
        `price`       DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
        `days`        VARCHAR(80)      NOT NULL DEFAULT '',
        `active`      TINYINT(1)       NOT NULL DEFAULT 1,
        `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
        `cp_ranges`   JSON             DEFAULT NULL,
        UNIQUE KEY `uq_sz_code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if ((int)$db->query("SELECT COUNT(*) FROM shipping_zones")->fetchColumn() === 0) {
        $db->exec("INSERT INTO `shipping_zones`
            (`code`, `name`, `description`, `price`, `days`, `active`, `sort_order`, `cp_ranges`)
            VALUES
            ('CABA',     'CABA',              'Ciudad Autónoma de Buenos Aires',            3500.00, '2 a 3 días hábiles', 1, 1, '[{\"from\":1000,\"to\":1499}]'),
            ('GBA',      'GBA',               'Gran Buenos Aires (Norte, Oeste y Sur)',      4500.00, '3 a 5 días hábiles', 1, 2, '[{\"from\":1500,\"to\":1999},{\"from\":6000,\"to\":7999}]'),
            ('INTERIOR', 'Interior del País', 'Córdoba, Rosario, Mendoza y resto del país', 7000.00, '5 a 8 días hábiles', 1, 3, '[{\"from\":2000,\"to\":5999},{\"from\":8000,\"to\":9999}]')
        ");
    }
}

try {
    pbEnsureShippingZones($db);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $zones = $db->query("SELECT * FROM shipping_zones ORDER BY sort_order ASC, id ASC")->fetchAll();
        foreach ($zones as &$z) {
            $z['price']     = (float)$z['price'];
            $z['active']    = (int)$z['active'] === 1;
            $z['cp_ranges'] = json_decode($z['cp_ranges'] ?? '[]', true) ?: [];
        }
        unset($z);
        jsonResponse(['zones' => $zones]);

    } elseif ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) jsonResponse(['error' => 'ID inválido'], 400);

        $body   = getJsonBody();
        $sets   = [];
        $params = [];

        if (isset($body['price'])) { $sets[] = 'price = ?'; $params[] = max(0, (float)$body['price']); }
        if (isset($body['days']))  { $sets[] = 'days = ?';  $params[] = trim((string)$body['days']);   }
        if (isset($body['name']))  { $sets[] = 'name = ?';  $params[] = trim((string)$body['name']);   }

        if (empty($sets)) jsonResponse(['error' => 'Nada para actualizar'], 400);

        $params[] = $id;
        $db->prepare("UPDATE shipping_zones SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
        jsonResponse(['ok' => true]);

    } else {
        jsonResponse(['error' => 'Método no permitido'], 405);
    }

} catch (Exception $e) {
    error_log('shipping_zones admin error: ' . $e->getMessage());
    jsonResponse(['error' => 'Error del servidor'], 500);
}
