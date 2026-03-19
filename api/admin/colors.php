<?php
/**
 * PrintingBruno - Admin API: Product colors CRUD
 * GET    /api/admin/colors.php
 * POST   /api/admin/colors.php
 * PUT    /api/admin/colors.php?id=X
 * DELETE /api/admin/colors.php?id=X
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../product_variants.php';
require_once __DIR__ . '/audit.php';

if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($method) {
        case 'GET':
            $onlyActive = isset($_GET['active']) && $_GET['active'] === '1';
            jsonResponse(['colors' => pbListProductColors($db, !$onlyActive)]);
            break;

        case 'POST':
            adminRequireCsrf();
            $color = pbUpsertAdminColor($db, null, getJsonBody());
            adminAuditLog('create', 'product_color', (int)$color['id'], ['name' => $color['name']]);
            jsonResponse(['success' => true, 'color' => $color], 201);
            break;

        case 'PUT':
            adminRequireCsrf();
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['error' => 'Color ID required'], 400);
            }
            $color = pbUpsertAdminColor($db, $id, getJsonBody());
            adminAuditLog('update', 'product_color', $id, ['name' => $color['name']]);
            jsonResponse(['success' => true, 'color' => $color]);
            break;

        case 'DELETE':
            adminRequireCsrf();
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['error' => 'Color ID required'], 400);
            }
            $usageStmt = $db->prepare('SELECT COUNT(*) FROM product_variants WHERE primary_color_id = ? OR secondary_color_id = ?');
            $usageStmt->execute([$id, $id]);
            $usageCount = (int)$usageStmt->fetchColumn();

            if ($usageCount > 0) {
                $stmt = $db->prepare('UPDATE product_colors SET active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                $stmt->execute([$id]);
                adminAuditLog('deactivate', 'product_color', $id, ['reason' => 'in_use']);
                jsonResponse(['success' => true, 'note' => 'El color estaba en uso y se desactivó en lugar de borrarse.']);
            }

            $stmt = $db->prepare('DELETE FROM product_colors WHERE id = ?');
            $stmt->execute([$id]);
            adminAuditLog('delete', 'product_color', $id);
            jsonResponse(['success' => true]);
            break;

        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (InvalidArgumentException $e) {
    jsonResponse(['error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log('admin/colors error: ' . $e->getMessage());
    jsonResponse(['error' => 'Server error'], 500);
}

function pbUpsertAdminColor(PDO $db, ?int $id, array $payload): array {
    $name = trim((string)($payload['name'] ?? ''));
    if ($name === '') {
        throw new InvalidArgumentException('El nombre del color es obligatorio.');
    }

    $hexPrimary = pbNormalizeColorHex($payload['hex_primary'] ?? null);
    $hexSecondary = pbNormalizeColorHex($payload['hex_secondary'] ?? null);
    if ($hexPrimary === null) {
        throw new InvalidArgumentException('Ingresá un color principal válido.');
    }

    $rawSlug = pbColorSlug(trim((string)($payload['slug'] ?? $name)));
    if ($rawSlug === '') {
        throw new InvalidArgumentException('No se pudo generar un slug válido para el color.');
    }
    // Ensure slug uniqueness
    $slugCheckQ  = $id === null
        ? 'SELECT COUNT(*) FROM product_colors WHERE slug = ?'
        : 'SELECT COUNT(*) FROM product_colors WHERE slug = ? AND id != ?';
    $slugParams  = $id === null ? [$rawSlug] : [$rawSlug, $id];
    $slugStmt    = $db->prepare($slugCheckQ);
    $slugStmt->execute($slugParams);
    $slug = (int)$slugStmt->fetchColumn() > 0 ? $rawSlug . '-' . time() : $rawSlug;

    $active = array_key_exists('active', $payload) ? (int)((bool)$payload['active']) : 1;
    $sortOrder = max(0, (int)($payload['sort_order'] ?? 0));

    if ($id === null) {
        $stmt = $db->prepare('INSERT INTO product_colors (name, slug, hex_primary, hex_secondary, active, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $slug, $hexPrimary, $hexSecondary, $active, $sortOrder]);
        $id = (int)$db->lastInsertId();
    } else {
        $stmt = $db->prepare('UPDATE product_colors SET name = ?, slug = ?, hex_primary = ?, hex_secondary = ?, active = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$name, $slug, $hexPrimary, $hexSecondary, $active, $sortOrder, $id]);
    }

    $stmt = $db->prepare('SELECT * FROM product_colors WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $color = $stmt->fetch();
    if (!$color) {
        throw new RuntimeException('No se pudo cargar el color guardado.');
    }

    $color['id'] = (int)$color['id'];
    $color['active'] = (int)$color['active'];
    $color['sort_order'] = (int)$color['sort_order'];
    return $color;
}
