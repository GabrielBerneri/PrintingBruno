<?php
/**
 * PrintingBruno - Local payment sync fallback
 * Permite actualizar el estado de una orden al volver desde Mercado Pago
 * cuando el webhook no puede llegar a localhost.
 *
 * GET /api/sync_payment.php?order=1&payment_id=123&status=approved
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

function mapMpStatus(?string $status): string {
    $status = strtolower(trim((string)$status));

    return match ($status) {
        'approved'              => 'approved',
        'pending'               => 'pending',
        'in_process',
        'authorized'            => 'in_process',
        'rejected',
        'failure', 'failed'     => 'rejected',
        'cancelled'             => 'cancelled',
        'refunded'              => 'refunded',
        'charged_back'          => 'charged_back',
        default                 => 'pending',
    };
}

try {
    $orderId = (int)($_GET['order'] ?? $_GET['external_reference'] ?? 0);
    $paymentId = (int)($_GET['payment_id'] ?? $_GET['collection_id'] ?? 0);

    if ($orderId <= 0) {
        jsonResponse(['error' => 'Order ID required'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare('SELECT id, status, order_number FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        jsonResponse(['error' => 'Order not found'], 404);
    }

    // SEGURIDAD: Si se proporciona payment_id, SIEMPRE verificar contra la API de MP.
    // Nunca confiar en el status recibido por query para estados positivos (approved).
    if ($paymentId > 0) {
        MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);
        $paymentClient = new PaymentClient();
        $payment = $paymentClient->get($paymentId);

        if (!$payment) {
            jsonResponse(['error' => 'Payment not found in MercadoPago'], 404);
        }

        // Verificar que el pago corresponde a esta orden (external_reference)
        $paymentOrderId = (int)($payment->external_reference ?? 0);
        if ($paymentOrderId !== $orderId) {
            jsonResponse(['error' => 'Payment does not match order'], 400);
        }

        $status = mapMpStatus($payment->status ?? 'pending');
        $merchantOrderId = $payment->order->id ?? null;
        $oldStatus = $order['status'];

        $stmt = $db->prepare(
            'UPDATE orders SET status = ?, mp_payment_id = ?, mp_merchant_order_id = COALESCE(?, mp_merchant_order_id), updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([
            $status,
            (string)$paymentId,
            $merchantOrderId ? (string)$merchantOrderId : null,
            $orderId,
        ]);

        // Descontar stock al aprobar (idempotencia: solo si antes no estaba approved)
        if ($status === 'approved' && $oldStatus !== 'approved') {
            $items = $db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
            $items->execute([$orderId]);
            foreach ($items->fetchAll() as $item) {
                $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?')
                   ->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            }
        }

        // Restituir stock si era approved y ahora es refunded, cancelled o charged_back
        if ($oldStatus === 'approved' && in_array($status, ['refunded', 'cancelled', 'charged_back'], true)) {
            $items = $db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
            $items->execute([$orderId]);
            foreach ($items->fetchAll() as $item) {
                $db->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')
                   ->execute([$item['quantity'], $item['product_id']]);
            }
        }

        jsonResponse([
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $order['order_number'],
            'payment_id' => $paymentId,
            'status' => $status,
        ]);
    }

    // Sin payment_id: solo devolver el estado actual sin modificar nada
    jsonResponse([
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $order['order_number'],
        'payment_id' => null,
        'status' => $order['status'],
    ]);
} catch (Exception $e) {
    error_log('sync_payment error: ' . $e->getMessage());
    jsonResponse(['error' => 'Error syncing payment'], 500);
}
