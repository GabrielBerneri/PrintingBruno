<?php
/**
 * PrintingBruno - Products API
 * GET /api/products.php           → List all active products
 * GET /api/products.php?id=X      → Single product
 * GET /api/products.php?slug=X    → Single product by slug
 * GET /api/products.php?category=X → Filter by category
 * GET /api/products.php?featured=1 → Featured only
 * GET /api/products.php?in_stock=1 → Only products with stock > 0
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/order_utils.php';
require_once __DIR__ . '/product_variants.php';

try {
    $db = getDB();
    pbExpireReservations($db);
    $hasImageUrlsColumn = hasImageUrlsColumn($db);
    
    // Campos públicos — no exponer active, created_at, updated_at (campos internos)
    $publicFields = 'id, name, slug, description, price, category, image_url, badge, material, stock, featured';
    if ($hasImageUrlsColumn) {
        $publicFields .= ', image_urls';
    }

    // Single product
    if (isset($_GET['id']) || isset($_GET['slug'])) {
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT $publicFields FROM products WHERE id = ? AND active = 1");
            $stmt->execute([(int)$_GET['id']]);
        } else {
            $stmt = $db->prepare("SELECT $publicFields FROM products WHERE slug = ? AND active = 1");
            $stmt->execute([trim((string)$_GET['slug'])]);
        }
        $product = $stmt->fetch();

        if (!$product) {
            jsonResponse(['error' => 'Product not found'], 404);
        }

        $product['price'] = (float)$product['price'];
        enrichAvailableStock($db, $product);
        enrichProductImages($product, $hasImageUrlsColumn);
        $singleProductList = [$product];
        pbAttachVariantsToProducts($db, $singleProductList, false);
        $singleProductList[0]['stock'] = (int)($singleProductList[0]['available_stock'] ?? $singleProductList[0]['stock'] ?? 0);
        jsonResponse($singleProductList[0]);
    }
    
    // Build query
    $where = ['active = 1'];
    $params = [];
    
    if (!empty($_GET['category'])) {
        $where[] = 'category = ?';
        $params[] = $_GET['category'];
    }
    
    if (isset($_GET['featured']) && $_GET['featured'] == '1') {
        $where[] = 'featured = 1';
    }

    $filterAvailableOnly = (isset($_GET['in_stock']) && $_GET['in_stock'] === '1') || (isset($_GET['available']) && $_GET['available'] === '1');
    
    $whereClause = implode(' AND ', $where);
    
    // Sort
    $orderBy = 'featured DESC, created_at DESC';
    if (!empty($_GET['sort'])) {
        switch ($_GET['sort']) {
            case 'name-asc': $orderBy = 'name ASC'; break;
            case 'name-desc': $orderBy = 'name DESC'; break;
            case 'price-asc': $orderBy = 'price ASC'; break;
            case 'price-desc': $orderBy = 'price DESC'; break;
            case 'newest': $orderBy = 'created_at DESC'; break;
        }
    }
    
    $sql = "SELECT $publicFields FROM products WHERE $whereClause ORDER BY $orderBy";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Cast prices to float
    foreach ($products as &$p) {
        $p['price'] = (float)$p['price'];
        enrichAvailableStock($db, $p);
        enrichProductImages($p, $hasImageUrlsColumn);
    }
    unset($p);

    pbAttachVariantsToProducts($db, $products, false);
    foreach ($products as &$p) {
        $p['stock'] = (int)($p['available_stock'] ?? $p['stock'] ?? 0);
    }
    unset($p);

    if ($filterAvailableOnly) {
        $products = array_values(array_filter($products, static function (array $product) {
            return (int)($product['available_stock'] ?? $product['stock'] ?? 0) > 0;
        }));
    }
    
    jsonResponse(['products' => $products, 'count' => count($products)]);
    
} catch (Exception $e) {
    jsonResponse(['error' => 'Server error'], 500);
}

function hasImageUrlsColumn(PDO $db): bool {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM products LIKE 'image_urls'");
        return (bool)$stmt->fetch();
    } catch (Throwable $e) {
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

    return array_values(array_unique($urls));
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

function enrichAvailableStock(PDO $db, array &$product): void {
    $productId = (int)($product['id'] ?? 0);
    if ($productId <= 0) {
        $product['available_stock'] = max(0, (int)($product['stock'] ?? 0));
        return;
    }

    $reserved = pbGetReservedQuantities($db, [$productId]);
    $available = max(0, (int)$product['stock'] - (int)($reserved[$productId] ?? 0));
    $product['available_stock'] = $available;
    $product['stock'] = $available;
}
