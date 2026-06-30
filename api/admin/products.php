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
require_once __DIR__ . '/../order_utils.php';
require_once __DIR__ . '/../product_variants.php';
require_once __DIR__ . '/audit.php';

// Auth check
if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

adminRequireCsrf();

$db = getDB();
$hasImageUrlsColumn = hasImageUrlsColumn($db);
$hasTransferDiscountColumn = pbHasColumn($db, 'products', 'transfer_discount');
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $db->query("SELECT * FROM products ORDER BY created_at DESC");
            $products = $stmt->fetchAll();
            $reservedByProduct = pbGetReservedQuantities($db, array_column($products, 'id'));
            foreach ($products as &$p) {
                $p['price'] = (float)$p['price'];
                $p['reserved_stock'] = (int)($reservedByProduct[(int)$p['id']] ?? 0);
                $p['available_stock'] = max(0, (int)$p['stock'] - $p['reserved_stock']);
                $p['transfer_discount'] = $hasTransferDiscountColumn ? (int)($p['transfer_discount'] ?? 0) : 0;
                enrichProductImages($p, $hasImageUrlsColumn);
            }
            unset($p);
            pbAttachVariantsToProducts($db, $products, true);
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

            $imageUrls = normalizeImageUrls($body['image_urls'] ?? ($body['image_url'] ?? ''));
            $primaryImage = $imageUrls[0] ?? '';
            $imageUrlsJson = $hasImageUrlsColumn ? json_encode($imageUrls, JSON_UNESCAPED_SLASHES) : null;
            
            $db->beginTransaction();

            if ($hasImageUrlsColumn) {
                $columns = 'name, slug, description, price, category, image_url, image_urls, badge, material, stock, active, featured';
                $params = [
                    $body['name'],
                    $slug,
                    $body['description'] ?? '',
                    (float)$body['price'],
                    $body['category'],
                    $primaryImage,
                    $imageUrlsJson,
                    $body['badge'] ?? null,
                    $body['material'] ?? 'PLA',
                    (int)($body['stock'] ?? 0),
                    (int)($body['active'] ?? 1),
                    (int)($body['featured'] ?? 0),
                ];
            } else {
                $columns = 'name, slug, description, price, category, image_url, badge, material, stock, active, featured';
                $params = [
                    $body['name'],
                    $slug,
                    $body['description'] ?? '',
                    (float)$body['price'],
                    $body['category'],
                    $primaryImage,
                    $body['badge'] ?? null,
                    $body['material'] ?? 'PLA',
                    (int)($body['stock'] ?? 0),
                    (int)($body['active'] ?? 1),
                    (int)($body['featured'] ?? 0),
                ];
            }
            if ($hasTransferDiscountColumn) {
                $columns .= ', transfer_discount';
                $params[] = (int)($body['transfer_discount'] ?? 0);
            }
            $placeholders = implode(', ', array_fill(0, count($params), '?'));
            $stmt = $db->prepare("INSERT INTO products ($columns) VALUES ($placeholders)");
            $stmt->execute($params);

            $newId = (int)$db->lastInsertId();
            pbSaveProductVariants($db, $newId, $body['variants'] ?? [], [
                'price' => (float)$body['price'],
                'stock' => (int)($body['stock'] ?? 0),
                'image_url' => $primaryImage,
                'image_urls' => $imageUrls,
            ]);
            $db->commit();

            adminAuditLog('create', 'product', $newId, [
                'name' => $body['name'],
                'price' => (float)$body['price'],
                'category' => $body['category'],
            ]);
            jsonResponse(['success' => true, 'id' => $newId], 201);
            break;
            
        case 'PUT':
            if (empty($_GET['id'])) {
                jsonResponse(['error' => 'Product ID required'], 400);
            }
            
            $body = getJsonBody();
            $id = (int)$_GET['id'];
            $variantFallbackImages = [];
            
            // Build dynamic update
            $fields = ['name', 'description', 'price', 'category', 'badge', 'material', 'stock', 'active', 'featured'];
            if ($hasTransferDiscountColumn) {
                $fields[] = 'transfer_discount';
            }
            $updates = [];
            $params = [];
            
            foreach ($fields as $field) {
                if (isset($body[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $body[$field];
                }
            }

            if (array_key_exists('image_urls', $body)) {
                $imageUrls = normalizeImageUrls($body['image_urls']);
                $variantFallbackImages = $imageUrls;
                $updates[] = 'image_url = ?';
                $params[] = $imageUrls[0] ?? '';
                if ($hasImageUrlsColumn) {
                    $updates[] = 'image_urls = ?';
                    $params[] = json_encode($imageUrls, JSON_UNESCAPED_SLASHES);
                }
            } elseif (array_key_exists('image_url', $body)) {
                $imageUrls = normalizeImageUrls($body['image_url']);
                $variantFallbackImages = $imageUrls;
                $updates[] = 'image_url = ?';
                $params[] = $imageUrls[0] ?? '';
                if ($hasImageUrlsColumn) {
                    $updates[] = 'image_urls = ?';
                    $params[] = json_encode($imageUrls, JSON_UNESCAPED_SLASHES);
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

            $db->beginTransaction();
            $db->prepare($sql)->execute($params);
            if (array_key_exists('variants', $body)) {
                pbSaveProductVariants($db, $id, $body['variants'], [
                    'price' => isset($body['price']) ? (float)$body['price'] : null,
                    'stock' => (int)($body['stock'] ?? 0),
                    'image_url' => $variantFallbackImages[0] ?? ($body['image_url'] ?? ''),
                    'image_urls' => $variantFallbackImages,
                ]);
            }
            $db->commit();
            adminAuditLog('update', 'product', $id, ['fields' => array_keys($body)]);
            
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
                adminAuditLog('deactivate', 'product', $id, ['reason' => 'has_orders']);
                jsonResponse(['success' => true, 'note' => 'Este producto tiene pedidos asociados, por lo que se marcó como "Inactivo" en lugar de borrarse permanentemente.']);
            } else {
                $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
                adminAuditLog('delete', 'product', $id);
                jsonResponse(['success' => true]);
            }
            break;
    }
} catch (Exception $e) {
    if ($db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
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

function hasImageUrlsColumn(PDO $db): bool {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM products LIKE 'image_urls'");
        return (bool)$stmt->fetch();
    } catch (Throwable $e) {
        error_log('admin/products hasImageUrlsColumn warning: ' . $e->getMessage());
        return false;
    }
}

function normalizeImageUrls($value): array {
    $urls = [];

    if (is_array($value)) {
        $urls = $value;
    } elseif (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed !== '') {
            if ($trimmed[0] === '[') {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    $urls = $decoded;
                } else {
                    $urls = [$trimmed];
                }
            } elseif (strpos($trimmed, ',') !== false) {
                $urls = array_map('trim', explode(',', $trimmed));
            } else {
                $urls = [$trimmed];
            }
        }
    }

    $urls = array_values(array_filter(array_map(static function ($u) {
        return is_string($u) ? trim($u) : '';
    }, $urls), static function ($u) {
        return $u !== '';
    }));

    $urls = array_values(array_unique($urls));

    if (count($urls) > 10) {
        $urls = array_slice($urls, 0, 10);
    }

    return $urls;
}

function enrichProductImages(array &$product, bool $hasImageUrlsColumn): void {
    $urls = [];

    if ($hasImageUrlsColumn && isset($product['image_urls']) && is_string($product['image_urls']) && $product['image_urls'] !== '') {
        $decoded = json_decode($product['image_urls'], true);
        if (is_array($decoded)) {
            $urls = normalizeImageUrls($decoded);
        }
    }

    if (empty($urls) && !empty($product['image_url'])) {
        $urls = normalizeImageUrls($product['image_url']);
    }

    $product['image_urls'] = $urls;
    $product['image_url'] = $urls[0] ?? '';
}
