<?php
/**
 * PrintingBruno - Admin API: Products CRUD
 * GET    /api/admin/products.php        → List all products (incl. inactive)
 * POST   /api/admin/products.php        → Create product
 * PUT    /api/admin/products.php?id=X   → Update product
 * DELETE /api/admin/products.php?id=X   → Delete product
 */

require_once __DIR__ . '/session.php';

require_once __DIR__ . '/../db.php';

// Auth check
if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $db->query("SELECT * FROM products ORDER BY created_at DESC");
            $products = $stmt->fetchAll();
            foreach ($products as &$p) $p['price'] = (float)$p['price'];
            jsonResponse(['products' => $products]);
            break;
            
        case 'POST':
            $body = getJsonBody();
            $required = ['name', 'price', 'category'];
            foreach ($required as $field) {
                if (empty($body[$field])) {
                    jsonResponse(['error' => "$field is required"], 400);
                }
            }
            
            $slug = createSlug($body['name'], $db);
            
            $stmt = $db->prepare("INSERT INTO products (name, slug, description, price, category, image_url, badge, material, stock, active, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $body['name'],
                $slug,
                $body['description'] ?? '',
                (float)$body['price'],
                $body['category'],
                $body['image_url'] ?? '',
                $body['badge'] ?? null,
                $body['material'] ?? 'PLA',
                (int)($body['stock'] ?? 0),
                (int)($body['active'] ?? 1),
                (int)($body['featured'] ?? 0),
            ]);
            
            jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
            break;
            
        case 'PUT':
            if (empty($_GET['id'])) {
                jsonResponse(['error' => 'Product ID required'], 400);
            }
            
            $body = getJsonBody();
            $id = (int)$_GET['id'];
            
            // Build dynamic update
            $fields = ['name', 'description', 'price', 'category', 'image_url', 'badge', 'material', 'stock', 'active', 'featured'];
            $updates = [];
            $params = [];
            
            foreach ($fields as $field) {
                if (isset($body[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $body[$field];
                }
            }
            
            if (empty($updates)) {
                jsonResponse(['error' => 'No fields to update'], 400);
            }
            
            // Update slug if name changed
            if (isset($body['name'])) {
                $updates[] = "slug = ?";
                $params[] = createSlug($body['name'], $db, $id);
            }
            
            $params[] = $id;
            $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = ?";
            $db->prepare($sql)->execute($params);
            
            jsonResponse(['success' => true]);
            break;
            
        case 'DELETE':
            if (empty($_GET['id'])) {
                jsonResponse(['error' => 'Product ID required'], 400);
            }
            
            $id = (int)$_GET['id'];
            
            // Check if product has orders
            $stmt = $db->prepare("SELECT COUNT(*) as c FROM order_items WHERE product_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch()['c'];
            
            if ($count > 0) {
                // Soft delete - just deactivate
                $db->prepare("UPDATE products SET active = 0 WHERE id = ?")->execute([$id]);
                jsonResponse(['success' => true, 'note' => 'Product deactivated (has orders)']);
            } else {
                $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
                jsonResponse(['success' => true]);
            }
            break;
    }
} catch (Exception $e) {
    error_log('admin/products error: ' . $e->getMessage());
    jsonResponse(['error' => 'Server error'], 500);
}

function createSlug(string $name, PDO $db, ?int $excludeId = null): string {
    $slug = mb_strtolower($name);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Check uniqueness
    $query = "SELECT COUNT(*) as c FROM products WHERE slug = ?";
    $params = [$slug];
    if ($excludeId) {
        $query .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    if ($stmt->fetch()['c'] > 0) {
        $slug .= '-' . time();
    }
    
    return $slug;
}
