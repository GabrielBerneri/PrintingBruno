<?php
/**
 * PrintingBruno - MercadoPago Checkout Pro
 * POST /api/create_preference.php
 * 
 * Body: {
 *   "items": [{ "id": 1, "quantity": 2 }, ...],
 *   "customer": { "name": "...", "email": "...", "phone": "..." }
 * }
 * 
 * Returns: { "init_point": "https://...", "preference_id": "...", "order_id": ... }
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $body = getJsonBody();
    
    // Validate input
    if (empty($body['items']) || !is_array($body['items'])) {
        jsonResponse(['error' => 'Items are required'], 400);
    }
    if (empty($body['customer']['name']) || empty($body['customer']['email'])) {
        jsonResponse(['error' => 'Customer name and email are required'], 400);
    }
    if (!filter_var($body['customer']['email'], FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Email inválido'], 400);
    }
    
    $db = getDB();
    
    // Fetch products from DB and calculate total
    $productIds = array_column($body['items'], 'id');
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND active = 1");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();
    
    // Index by ID
    $productsById = [];
    foreach ($products as $p) {
        $productsById[$p['id']] = $p;
    }
    
    // Build MP items and calculate total
    $mpItems = [];
    $total = 0;
    $orderItems = [];
    
    foreach ($body['items'] as $item) {
        $pid = (int)$item['id'];
        $qty = max(1, min(99, (int)($item['quantity'] ?? 1))); // límite: 1-99 unidades
        
        if (!isset($productsById[$pid])) {
            jsonResponse(['error' => "Product ID $pid not found"], 400);
        }
        
        $product = $productsById[$pid];
        $price = (float)$product['price'];
        $total += $price * $qty;
        
        $mpItems[] = [
            'id' => (string)$pid,
            'title' => $product['name'],
            'description' => mb_substr($product['description'] ?? '', 0, 200),
            'quantity' => $qty,
            'unit_price' => $price,
            'currency_id' => 'ARS',
            'picture_url' => SITE_URL . '/' . $product['image_url'],
        ];
        
        $orderItems[] = [
            'product_id' => $pid,
            'quantity' => $qty,
            'unit_price' => $price,
        ];
    }
    
    // Create order in DB
    $stmt = $db->prepare("INSERT INTO orders (customer_name, customer_email, customer_phone, total, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $body['customer']['name'],
        $body['customer']['email'],
        $body['customer']['phone'] ?? null,
        $total,
    ]);
    $orderId = (int)$db->lastInsertId();
    
    // Insert order items
    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
    foreach ($orderItems as $oi) {
        $stmt->execute([$orderId, $oi['product_id'], $oi['quantity'], $oi['unit_price']]);
    }
    
    // Configure MercadoPago
    MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);
    
    // Create preference
    $client = new PreferenceClient();
    $preference = $client->create([
        'items' => $mpItems,
        'payer' => [
            'name' => $body['customer']['name'],
            'email' => $body['customer']['email'],
            'phone' => [
                'number' => $body['customer']['phone'] ?? '',
            ],
        ],
        'back_urls' => [
            'success' => MP_SUCCESS_URL . '?order=' . $orderId,
            'failure' => MP_FAILURE_URL . '?order=' . $orderId,
            'pending' => MP_PENDING_URL . '?order=' . $orderId,
        ],
        'auto_return' => 'approved',
        'notification_url' => MP_WEBHOOK_URL,
        'external_reference' => (string)$orderId,
        'statement_descriptor' => SITE_NAME,
    ]);
    
    // Update order with preference ID
    $stmt = $db->prepare("UPDATE orders SET mp_preference_id = ? WHERE id = ?");
    $stmt->execute([$preference->id, $orderId]);
    
    jsonResponse([
        'init_point' => $preference->init_point,
        'preference_id' => $preference->id,
        'order_id' => $orderId,
        'total' => $total,
    ]);
    
} catch (MPApiException $e) {
    // No exponer detalles internos de MP al frontend
    error_log('MercadoPago API error: ' . json_encode($e->getApiResponse()->getContent()));
    jsonResponse(['error' => 'Error al procesar el pago. Por favor intente nuevamente.'], 500);
} catch (Exception $e) {
    error_log('create_preference error: ' . $e->getMessage());
    jsonResponse(['error' => 'Error del servidor. Por favor intente nuevamente.'], 500);
}
