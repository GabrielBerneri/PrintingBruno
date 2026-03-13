<?php
/**
 * PrintingBruno - Products API
 * GET /api/products.php           → List all active products
 * GET /api/products.php?id=X      → Single product
 * GET /api/products.php?slug=X    → Single product by slug
 * GET /api/products.php?category=X → Filter by category
 * GET /api/products.php?featured=1 → Featured only
 */

require_once __DIR__ . '/db.php';

try {
    $db = getDB();
    
    // Single product
    if (isset($_GET['id']) || isset($_GET['slug'])) {
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
            $stmt->execute([(int)$_GET['id']]);
        } else {
            $stmt = $db->prepare("SELECT * FROM products WHERE slug = ? AND active = 1");
            $stmt->execute([trim((string)$_GET['slug'])]);
        }
        $product = $stmt->fetch();
        
        if (!$product) {
            jsonResponse(['error' => 'Product not found'], 404);
        }
        
        $product['price'] = (float)$product['price'];
        jsonResponse($product);
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
    
    $sql = "SELECT * FROM products WHERE $whereClause ORDER BY $orderBy";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Cast prices to float
    foreach ($products as &$p) {
        $p['price'] = (float)$p['price'];
    }
    
    jsonResponse(['products' => $products, 'count' => count($products)]);
    
} catch (Exception $e) {
    jsonResponse(['error' => 'Server error'], 500);
}
