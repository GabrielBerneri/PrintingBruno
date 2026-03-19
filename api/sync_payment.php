<?php
/**
 * PrintingBruno - Local payment sync fallback
 * Permite actualizar el estado de una orden al volver desde Mercado Pago
 * cuando el webhook no puede llegar a localhost.
 *
 * GET /api/sync_payment.php?order=1&payment_id=123&status=approved
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/order_utils.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $orderId = (int)($_GET['order'] ?? $_GET['external_reference'] ?? 0);
    $paymentId = (int)($_GET['payment_id'] ?? $_GET['collection_id'] ?? 0);
    $orderToken = trim((string)($_GET['ot'] ?? ''));

    if ($orderId <= 0) {
        jsonResponse(['error' => 'Order ID required'], 400);
    }

    $db = getDB();
    pbExpireReservations($db);
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        jsonResponse(['error' => 'Order not found'], 404);
    }

    $orderNumber = (string)($order['order_number'] ?? '');
    $hasValidOrderToken = pbVerifyOrderAccessToken($orderId, $orderNumber, $orderToken);

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

        $paymentStatus = pbMapMercadoPagoStatusToPaymentStatus($payment->status ?? 'pending');
        $merchantOrderId = $payment->order->id ?? null;

        $db->beginTransaction();
        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new RuntimeException('Order not found during payment sync');
        }

        $nextLifecycle = pbResolveOrderLifecycle($order, [
            'payment_status' => $paymentStatus,
        ]);
        $transitionError = pbLifecycleTransitionError($order, $nextLifecycle);
        if ($transitionError !== null) {
            $db->rollBack();
            jsonResponse(['error' => $transitionError], 409);
        }

        pbApplyLifecycleTransitionEffects($db, $order, $nextLifecycle);

        $stmt = $db->prepare(
            "UPDATE orders
             SET status = ?, payment_status = ?, fulfillment_status = ?, mp_payment_id = ?, mp_merchant_order_id = COALESCE(?, mp_merchant_order_id),
                 checkout_status = ?,
                 payment_verified_at = CASE
                     WHEN ? = 'approved' AND payment_verified_at IS NULL THEN NOW()
                     ELSE payment_verified_at
                 END,
                 updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([
            $nextLifecycle['status'],
            $nextLifecycle['payment_status'],
            $nextLifecycle['fulfillment_status'],
            (string)$paymentId,
            $merchantOrderId ? (string)$merchantOrderId : null,
            $nextLifecycle['checkout_status'],
            $nextLifecycle['payment_status'],
            $orderId,
        ]);

        $db->commit();

        jsonResponse([
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'payment_id' => $paymentId,
            'status' => $nextLifecycle['status'],
            'payment_status' => $nextLifecycle['payment_status'],
            'fulfillment_status' => $nextLifecycle['fulfillment_status'],
        ]);
    }

    if (!$hasValidOrderToken) {
        jsonResponse(['error' => 'Unauthorized'], 403);
    }

    // Sin payment_id: solo devolver el estado actual sin modificar nada
    $currentLifecycle = pbGetOrderLifecycle($order);
    jsonResponse([
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'payment_id' => null,
        'status' => $currentLifecycle['status'],
        'payment_status' => $currentLifecycle['payment_status'],
        'fulfillment_status' => $currentLifecycle['fulfillment_status'],
    ]);
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('sync_payment error: ' . $e->getMessage());
    jsonResponse(['error' => 'Error syncing payment'], 500);
}
