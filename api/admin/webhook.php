<?php
/**
 * PrintingBruno - MercadoPago Webhook (IPN)
 * Recibe notificaciones de pago de MercadoPago.
 * POST /api/webhook.php
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;

// Solo acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Leer el body una sola vez (php://input solo se puede leer una vez)
$rawBody = file_get_contents('php://input');
$body    = json_decode($rawBody, true) ?? [];

/**
 * Verifica la firma HMAC-SHA256 del webhook de MercadoPago.
 * Documentación: https://www.mercadopago.com.ar/developers/es/docs/your-integrations/notifications/webhooks
 *
 * Si MP_WEBHOOK_SECRET no está configurado (placeholder), omite la verificación
 * en modo TEST para no romper el desarrollo local.
 */
function verifyWebhookSignature(string $dataId): void {
    $secret    = MP_WEBHOOK_SECRET;
    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';

    // Si el secret es el placeholder, omitir verificación (solo en credenciales TEST)
    if ($secret === 'your_webhook_secret_here') {
        if (str_starts_with(MP_ACCESS_TOKEN, 'TEST-')) {
            return; // modo desarrollo: no verificar
        }
        // En producción SIN secret configurado → rechazar todo
        jsonResponse(['error' => 'Webhook secret not configured'], 500);
    }

    if (empty($signature)) {
        jsonResponse(['error' => 'Missing webhook signature'], 401);
    }

    // Parsear "ts=TIMESTAMP,v1=HASH"
    $parts = [];
    foreach (explode(',', $signature) as $part) {
        $pair = explode('=', $part, 2);
        if (count($pair) === 2) {
            $parts[trim($pair[0])] = trim($pair[1]);
        }
    }

    $ts = $parts['ts'] ?? '';
    $v1 = $parts['v1'] ?? '';

    if (empty($ts) || empty($v1)) {
        jsonResponse(['error' => 'Invalid webhook signature format'], 401);
    }

    // Template a firmar según documentación de MercadoPago
    $template = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
    $computed = hash_hmac('sha256', $template, $secret);

    // hash_equals previene timing attacks
    if (!hash_equals($computed, $v1)) {
        jsonResponse(['error' => 'Invalid webhook signature'], 401);
    }
}

try {
    $type   = $body['type'] ?? $_GET['type'] ?? null;
    $dataId = $body['data']['id'] ?? $_GET['data_id'] ?? null;

    // Verificar firma antes de procesar cualquier dato
    if ($dataId) {
        verifyWebhookSignature((string)$dataId);
    }

    // Log del webhook (directorio protegido por logs/.htaccess)
    $logFile = __DIR__ . '/../logs/webhook_' . date('Y-m-d') . '.log';
    $logDir  = dirname($logFile);
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    file_put_contents(
        $logFile,
        date('Y-m-d H:i:s') . ' | Type: ' . $type . ' | DataID: ' . $dataId . "\n",
        FILE_APPEND | LOCK_EX
    );

    if ($type === 'payment' && $dataId) {
        MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

        $paymentClient = new PaymentClient();
        $payment       = $paymentClient->get((int)$dataId);

        if ($payment) {
            $paymentStatus = $payment->status;
            $externalRef   = $payment->external_reference; // nuestro order ID
            $merchantOrderId = $payment->order->id ?? null;

            $statusMap = [
                'approved'    => 'approved',
                'pending'     => 'pending',
                'in_process'  => 'in_process',
                'authorized'  => 'in_process',
                'rejected'    => 'rejected',
                'refunded'    => 'refunded',
                'cancelled'   => 'cancelled',
                'charged_back' => 'charged_back',
            ];

            $ourStatus = $statusMap[$paymentStatus] ?? null;
            if ($ourStatus === null) {
                file_put_contents(
                    $logFile,
                    date('Y-m-d H:i:s') . " | UNKNOWN MP STATUS: {$paymentStatus} order_id={$externalRef}\n",
                    FILE_APPEND | LOCK_EX
                );
                jsonResponse(['status' => 'ok']);
                exit;
            }

            $db   = getDB();
            
            // Check old status to handle stock restitution
            $stmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
            $stmt->execute([(int)$externalRef]);
            $oldOrder = $stmt->fetch();
            $oldStatus = $oldOrder['status'] ?? '';
            
            $stmt = $db->prepare(
                "UPDATE orders SET status = ?, mp_payment_id = ?, mp_merchant_order_id = ?, updated_at = NOW() WHERE id = ?"
            );
            $stmt->execute([
                $ourStatus,
                (string)$dataId,
                (string)$merchantOrderId,
                (int)$externalRef,
            ]);

            // Descontar stock solo en pago aprobado Y si no estaba ya aprobado (idempotencia)
            if ($ourStatus === 'approved' && $oldStatus !== 'approved') {
                $stmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $stmt->execute([(int)$externalRef]);
                $items = $stmt->fetchAll();

                foreach ($items as $item) {
                    // AND stock >= ? previene race condition: si stock es insuficiente, no descuenta
                    $updateStmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                    $updateStmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
                    if ($updateStmt->rowCount() === 0) {
                        // Stock insuficiente en el momento del descuento — loguear para revisión manual
                        file_put_contents(
                            $logFile,
                            date('Y-m-d H:i:s') . " | STOCK CONFLICT: product_id={$item['product_id']} qty={$item['quantity']} order_id={$externalRef}\n",
                            FILE_APPEND | LOCK_EX
                        );
                    }
                }
            }
            
            // Restituir stock si era approved y ahora es refunded, cancelled o charged_back
            if ($oldStatus === 'approved' && in_array($ourStatus, ['refunded', 'cancelled', 'charged_back'], true)) {
                $stmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $stmt->execute([(int)$externalRef]);
                $items = $stmt->fetchAll();

                foreach ($items as $item) {
                    $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
                       ->execute([$item['quantity'], $item['product_id']]);
                }
            }

            file_put_contents(
                $logFile,
                date('Y-m-d H:i:s') . " | Order #{$externalRef} → {$ourStatus}\n",
                FILE_APPEND | LOCK_EX
            );
        }
    }

    // MercadoPago requiere siempre 200
    jsonResponse(['status' => 'ok']);

} catch (Exception $e) {
    $errorLog = __DIR__ . '/../logs/webhook_errors.log';
    $logDir   = dirname($errorLog);
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    file_put_contents(
        $errorLog,
        date('Y-m-d H:i:s') . ' | Error: ' . $e->getMessage() . "\n",
        FILE_APPEND | LOCK_EX
    );

    // Responder 200 igualmente para que MP no reintente en bucle
    jsonResponse(['status' => 'ok']);
}
