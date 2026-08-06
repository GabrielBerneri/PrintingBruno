<?php
/**
 * Notificación interna al negocio cuando entra un nuevo pedido.
 *
 * Usage: sendOrderAdminNotification($orderId)
 *
 * A diferencia de la confirmación al cliente, este email resume lo que
 * el negocio necesita para preparar el pedido: quién compró, medio de
 * pago elegido y si retira en persona o requiere envío.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../order_shipping.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

if (!defined('ORDER_ADMIN_NOTIFICATION_EMAIL')) {
    define('ORDER_ADMIN_NOTIFICATION_EMAIL', pbEnv('ORDER_ADMIN_NOTIFICATION_EMAIL', 'contacto@printingbruno.com'));
}

function pbPaymentMethodLabel(?string $method): string {
    switch (strtolower(trim((string)$method))) {
        case 'transferencia': return 'Transferencia bancaria';
        case 'efectivo':      return 'Efectivo';
        case 'mercadopago':   return 'MercadoPago';
        default:              return $method !== null && $method !== '' ? (string)$method : 'No especificado';
    }
}

function sendOrderAdminNotification(int $orderId): bool {
    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) return false;

        // Items del pedido
        $stmt = $db->prepare("
            SELECT oi.*, p.name AS product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();

        $orderNumber  = $order['order_number'] ?? ('#' . $orderId);
        $customerName = trim((string)($order['customer_name'] ?? ''));
        $customerMail = trim((string)($order['customer_email'] ?? ''));
        $customerPhone = trim((string)($order['customer_phone'] ?? ''));
        $total        = number_format((float)$order['total'], 0, ',', '.');
        $paymentLabel = pbPaymentMethodLabel($order['payment_method'] ?? '');
        $createdAt    = $order['created_at'] ?? date('Y-m-d H:i:s');

        // Envío o retiro
        $shipping = pbGetOrderShippingAddress($db, $orderId);
        $hasAddress = $shipping && trim((string)($shipping['street'] ?? '')) !== '';
        if ($hasAddress) {
            $fulfillmentTitle = 'Envío a domicilio';
            $addressParts = array_filter([
                trim((string)($shipping['street'] ?? '')),
                trim((string)($shipping['city'] ?? '')),
                trim((string)($shipping['province'] ?? '')),
                trim((string)($shipping['postal_code'] ?? '')) !== '' ? 'CP ' . $shipping['postal_code'] : '',
            ]);
            $fulfillmentDetail = htmlspecialchars(implode(', ', $addressParts), ENT_QUOTES, 'UTF-8');
            $recipient = trim((string)($shipping['recipient_name'] ?? ''));
            if ($recipient !== '') {
                $fulfillmentDetail = 'Destinatario: ' . htmlspecialchars($recipient, ENT_QUOTES, 'UTF-8') . '<br>' . $fulfillmentDetail;
            }
            $shipMethod = trim((string)($order['shipping_method'] ?? ''));
            if ($shipMethod !== '') {
                $fulfillmentDetail .= '<br>Método: ' . htmlspecialchars($shipMethod, ENT_QUOTES, 'UTF-8');
            }
            $shipNotes = trim((string)($shipping['notes'] ?? ''));
            if ($shipNotes !== '') {
                $fulfillmentDetail .= '<br>Notas: ' . htmlspecialchars($shipNotes, ENT_QUOTES, 'UTF-8');
            }
        } else {
            $fulfillmentTitle = 'Retira en persona';
            $fulfillmentDetail = 'El cliente retira el pedido.';
        }

        // Tabla de productos
        $itemsHtml = '';
        foreach ($items as $item) {
            $qty = (int)$item['quantity'];
            $name = htmlspecialchars((string)$item['product_name'], ENT_QUOTES, 'UTF-8');
            $variantLabel = trim((string)($item['variant_label'] ?? ''));
            if ($variantLabel !== '') {
                $name .= " <span style='color:#888'>(" . htmlspecialchars($variantLabel, ENT_QUOTES, 'UTF-8') . ")</span>";
            }
            $lineTotal = number_format((float)$item['unit_price'] * $qty, 0, ',', '.');
            $itemsHtml .= "<tr>
                <td style='padding:8px;border-bottom:1px solid #eee'>{$name}</td>
                <td style='padding:8px;border-bottom:1px solid #eee;text-align:center'>{$qty}</td>
                <td style='padding:8px;border-bottom:1px solid #eee;text-align:right'>\${$lineTotal}</td>
            </tr>";
        }

        $safeName  = htmlspecialchars($customerName !== '' ? $customerName : 'Cliente', ENT_QUOTES, 'UTF-8');
        $safeMail  = htmlspecialchars($customerMail, ENT_QUOTES, 'UTF-8');
        $safePhone = htmlspecialchars($customerPhone !== '' ? $customerPhone : '—', ENT_QUOTES, 'UTF-8');
        $safeOrder = htmlspecialchars((string)$orderNumber, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:Arial,Helvetica,sans-serif;color:#1a1a2e">
    <div style="max-width:600px;margin:0 auto;padding:24px">
        <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08)">
            <div style="background:#111827;color:#fff;padding:20px 24px">
                <h1 style="margin:0;font-size:1.25rem">🛒 Nuevo pedido {$safeOrder}</h1>
                <p style="margin:4px 0 0;font-size:0.85rem;color:#c7c7d9">{$createdAt}</p>
            </div>
            <div style="padding:24px">
                <table style="width:100%;border-collapse:collapse;margin-bottom:20px">
                    <tr>
                        <td style="padding:6px 0;color:#666;width:140px">Comprado por</td>
                        <td style="padding:6px 0;font-weight:600">{$safeName}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#666">Email</td>
                        <td style="padding:6px 0"><a href="mailto:{$safeMail}" style="color:#2563eb">{$safeMail}</a></td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#666">Teléfono</td>
                        <td style="padding:6px 0">{$safePhone}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#666">Medio de pago</td>
                        <td style="padding:6px 0;font-weight:600">{$paymentLabel}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#666;vertical-align:top">Entrega</td>
                        <td style="padding:6px 0"><strong>{$fulfillmentTitle}</strong><br><span style="color:#444;font-size:0.9rem">{$fulfillmentDetail}</span></td>
                    </tr>
                </table>

                <h2 style="font-size:1rem;margin:0 0 8px;border-top:1px solid #eee;padding-top:16px">Productos</h2>
                <table style="width:100%;border-collapse:collapse;font-size:0.9rem">
                    <thead>
                        <tr style="text-align:left;color:#888">
                            <th style="padding:8px;border-bottom:2px solid #eee">Producto</th>
                            <th style="padding:8px;border-bottom:2px solid #eee;text-align:center">Cant.</th>
                            <th style="padding:8px;border-bottom:2px solid #eee;text-align:right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>{$itemsHtml}</tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="padding:12px 8px;text-align:right;font-weight:700">TOTAL</td>
                            <td style="padding:12px 8px;text-align:right;font-weight:700;font-size:1.1rem">\${$total}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <p style="text-align:center;color:#9a9aab;font-size:0.75rem;margin-top:16px">Notificación interna — PrintingBruno</p>
    </div>
</body>
</html>
HTML;

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress(ORDER_ADMIN_NOTIFICATION_EMAIL, 'PrintingBruno');
        if ($customerMail !== '') {
            $mail->addReplyTo($customerMail, $customerName !== '' ? $customerName : $customerMail);
        }

        $mail->isHTML(true);
        $entrega = $hasAddress ? 'Envío' : 'Retira';
        $mail->Subject = "🛒 Nuevo pedido {$orderNumber} — {$paymentLabel} · {$entrega}";
        $mail->Body    = $html;
        $mail->AltBody = "Nuevo pedido {$orderNumber}\nComprado por: {$customerName} ({$customerMail})\nTeléfono: {$customerPhone}\nMedio de pago: {$paymentLabel}\nEntrega: {$fulfillmentTitle}\nTotal: \${$total}";

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log("sendOrderAdminNotification error (order {$orderId}): " . $e->getMessage());
        return false;
    }
}
