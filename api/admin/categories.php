<?php
/**
 * PrintingBruno - Admin API: Product categories CRUD
 * GET    /api/admin/categories.php
 * POST   /api/admin/categories.php
 * PUT    /api/admin/categories.php?id=X
 * DELETE /api/admin/categories.php?id=X
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db.php';

if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$db = getDB();

header('Content-Type: application/json; charset=utf-8');

function pbEnsureCategoriesTable(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS product_categories (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(100) NOT NULL,
            slug       VARCHAR(100) NOT NULL,
            sort_order INT          NOT NULL DEFAULT 0,
            active     TINYINT(1)   NOT NULL DEFAULT 1,
            created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Si la tabla quedó vacía, importar las categorías que ya existen en products
    $count = (int)$db->query('SELECT COUNT(*) FROM product_categories')->fetchColumn();
    if ($count === 0) {
        $slugs = $db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $db->prepare('INSERT IGNORE INTO product_categories (name, slug, sort_order) VALUES (?, ?, ?)');
        foreach ($slugs as $i => $slug) {
            $name = ucfirst(str_replace(['-', '_'], ' ', $slug));
            $stmt->execute([$name, $slug, $i * 10]);
        }
    }
}

function pbCategorySlug(string $value): string {
    $slug = strtolower(trim($value));
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug) ?: $slug;
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim((string)$slug, '-');
}

try {
    pbEnsureCategoriesTable($db);

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $id     = isset($_GET['id']) && $_GET['id'] !== '' ? (int)$_GET['id'] : null;

    if ($method === 'GET') {
        $includeInactive = !empty($_GET['all']);
        $sql = 'SELECT * FROM product_categories';
        if (!$includeInactive) $sql .= ' WHERE active = 1';
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['id']         = (int)$r['id'];
            $r['sort_order'] = (int)$r['sort_order'];
            $r['active']     = (int)$r['active'];
        }
        unset($r);
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    if ($method === 'POST') {
        $name = trim($body['name'] ?? '');
        if ($name === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'El nombre es obligatorio']);
            exit;
        }
        $slug   = trim($body['slug'] ?? '') ?: pbCategorySlug($name);
        $order  = (int)($body['sort_order'] ?? 0);
        $active = isset($body['active']) ? (int)(bool)$body['active'] : 1;
        $stmt   = $db->prepare('INSERT INTO product_categories (name, slug, sort_order, active) VALUES (?,?,?,?)');
        $stmt->execute([$name, $slug, $order, $active]);
        echo json_encode(['success' => true, 'id' => (int)$db->lastInsertId()]);
        exit;
    }

    if ($method === 'PUT') {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
            exit;
        }
        $name = trim($body['name'] ?? '');
        if ($name === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'El nombre es obligatorio']);
            exit;
        }
        $slug   = trim($body['slug'] ?? '') ?: pbCategorySlug($name);
        $order  = (int)($body['sort_order'] ?? 0);
        $active = isset($body['active']) ? (int)(bool)$body['active'] : 1;
        $stmt   = $db->prepare('UPDATE product_categories SET name=?, slug=?, sort_order=?, active=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
        $stmt->execute([$name, $slug, $order, $active, $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($method === 'DELETE') {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
            exit;
        }
        $row = $db->prepare('SELECT slug FROM product_categories WHERE id=?');
        $row->execute([$id]);
        $cat = $row->fetch(PDO::FETCH_ASSOC);
        if (!$cat) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Categoría no encontrada']);
            exit;
        }
        $count = $db->prepare('SELECT COUNT(*) FROM products WHERE category=?');
        $count->execute([$cat['slug']]);
        if ((int)$count->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'No se puede eliminar: hay productos con esta categoría']);
            exit;
        }
        $db->prepare('DELETE FROM product_categories WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no soportado']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
