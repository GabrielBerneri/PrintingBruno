<?php
/**
 * PrintingBruno - Email: Order Confirmation
 * Sends a styled HTML email to the customer with order details.
 * 
 * Usage: sendOrderConfirmation($orderId)
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send order confirmation email to the customer.
 * Returns true on success, false on failure (logs errors).
 */
function sendOrderConfirmation(int $orderId): bool {
    try {
        $db = getDB();

        // Fetch order
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) return false;

        // Fetch order items with product info
        $stmt = $db->prepare("
            SELECT oi.*, p.name as product_name, p.image_url 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();

        $orderNumber = $order['order_number'] ?? '#' . $orderId;
        $customerName = $order['customer_name'];
        $customerEmail = $order['customer_email'];
        $total = number_format((float)$order['total'], 0, ',', '.');

        // Build items HTML
        $itemsHtml = '';
        foreach ($items as $item) {
            $price = number_format((float)$item['unit_price'], 0, ',', '.');
            $subtotal = number_format((float)$item['unit_price'] * (int)$item['quantity'], 0, ',', '.');
            $imgUrl = SITE_URL . '/' . $item['image_url'];
            $itemsHtml .= "
                <tr>
                    <td style='padding:12px 8px;border-bottom:1px solid #2a2a3e'>
                        <img src='{$imgUrl}' alt='' width='44' height='44' style='border-radius:8px;object-fit:cover;vertical-align:middle;margin-right:8px'>
                        " . htmlspecialchars($item['product_name']) . "
                    </td>
                    <td style='padding:12px 8px;border-bottom:1px solid #2a2a3e;text-align:center'>{$item['quantity']}</td>
                    <td style='padding:12px 8px;border-bottom:1px solid #2a2a3e;text-align:right'>\${$price}</td>
                    <td style='padding:12px 8px;border-bottom:1px solid #2a2a3e;text-align:right;font-weight:600'>\${$subtotal}</td>
                </tr>";
        }

        // WhatsApp link with order number
        $waMessage = urlencode("Hola! Mi pedido es {$orderNumber}");
        $waLink = "https://wa.me/5491125544248?text={$waMessage}";

        // Build full email HTML
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0a0a1a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
    <div style="max-width:600px;margin:0 auto;padding:32px 16px">
        
        <!-- Header -->
        <div style="text-align:center;padding:24px 0;border-bottom:2px solid #ff6b2b">
            <span style="font-size:1.5rem;font-weight:800;color:#ffffff">Printing<span style="color:#ff6b2b">Bruno</span></span>
        </div>

        <!-- Order Number -->
        <div style="text-align:center;padding:40px 0 24px">
            <div style="font-size:0.9rem;color:#8a8aab;margin-bottom:8px">N° de Orden</div>
            <div style="font-size:1.6rem;font-weight:800;color:#ff6b2b;font-family:monospace;letter-spacing:1px;background:rgba(255,107,43,0.1);display:inline-block;padding:10px 24px;border-radius:8px">{$orderNumber}</div>
        </div>

        <!-- Greeting -->
        <div style="background:#12122a;border-radius:12px;padding:28px;margin-bottom:20px">
            <p style="color:#ffffff;font-size:1.05rem;margin:0 0 12px">Hola <strong>{$customerName}</strong>, gracias por tu compra.</p>
            <p style="color:#c0c0d8;font-size:0.95rem;margin:0;line-height:1.6">
                Este es tu numero de orden para realizar el seguimiento de tu compra.
            </p>
        </div>

        <!-- Items Table -->
        <div style="background:#12122a;border-radius:12px;padding:20px;margin-bottom:20px">
            <h3 style="color:#ffffff;font-size:0.95rem;margin:0 0 16px;font-weight:700">📦 Detalle del pedido</h3>
            <table style="width:100%;border-collapse:collapse;color:#c0c0d8;font-size:0.85rem">
                <thead>
                    <tr>
                        <th style="padding:8px;text-align:left;color:#8a8aab;font-size:0.75rem;text-transform:uppercase;border-bottom:1px solid #2a2a3e">Producto</th>
                        <th style="padding:8px;text-align:center;color:#8a8aab;font-size:0.75rem;text-transform:uppercase;border-bottom:1px solid #2a2a3e">Cant.</th>
                        <th style="padding:8px;text-align:right;color:#8a8aab;font-size:0.75rem;text-transform:uppercase;border-bottom:1px solid #2a2a3e">Precio</th>
                        <th style="padding:8px;text-align:right;color:#8a8aab;font-size:0.75rem;text-transform:uppercase;border-bottom:1px solid #2a2a3e">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
            </table>
            <div style="text-align:right;padding:16px 8px 0;border-top:2px solid #ff6b2b;margin-top:8px">
                <span style="color:#8a8aab;font-size:0.85rem">Total: </span>
                <span style="color:#ff6b2b;font-size:1.3rem;font-weight:800">\${$total}</span>
            </div>
        </div>

        <!-- Next Steps -->
        <div style="background:#12122a;border-radius:12px;padding:24px;margin-bottom:20px">
            <h3 style="color:#ffffff;font-size:0.95rem;margin:0 0 12px;font-weight:700">📋 Próximos pasos</h3>
            <p style="color:#c0c0d8;font-size:0.9rem;margin:0;line-height:1.7">
                Una vez confirmado el pago, nos pondremos en contacto para coordinar el envío o retiro de tu pedido.
                Si tenés alguna duda, podés contactarnos por WhatsApp con tu número de orden.
            </p>
        </div>

        <!-- CTA -->
        <div style="text-align:center;padding:16px 0">
            <a href="{$waLink}" style="display:inline-block;background:#25D366;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:700;font-size:0.95rem">
                💬 Contactarnos por WhatsApp
            </a>
        </div>

        <!-- Footer -->
        <div style="text-align:center;padding:24px 0;border-top:1px solid #1e1e36;margin-top:16px">
            <p style="color:#5a5a7a;font-size:0.8rem;margin:0">PrintingBruno — Impresiones 3D</p>
            <p style="color:#3a3a5a;font-size:0.7rem;margin:4px 0 0">Este email fue generado automáticamente. No respondas a este correo.</p>
        </div>

    </div>
</body>
</html>
HTML;

        // Send via PHPMailer
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
        $mail->addAddress($customerEmail, $customerName);

        $mail->isHTML(true);
        $subject = "PrintingBruno - Confirmación de Pedido {$orderNumber}";
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = "Hola {$customerName}, tu pedido {$orderNumber} fue recibido. Total: \${$total}. Guardá este número para consultas.";

        $mail->send();
        
        // Log success
        try {
            $stmt = $db->prepare("INSERT INTO email_logs (order_id, recipient_email, subject, body_html, status) VALUES (?, ?, ?, ?, 'sent')");
            $stmt->execute([$orderId, $customerEmail, $subject, $html]);
        } catch (\Exception $dbEx) {
            // If logging fails, we still consider the email sent, but we might log the DB error to file
            error_log("Failed to log email success to DB: " . $dbEx->getMessage());
        }

        return true;

    } catch (\Exception $e) {
        $logFile = __DIR__ . '/../../logs/email_errors.log';
        $logDir  = dirname($logFile);
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        file_put_contents(
            $logFile,
            date('Y-m-d H:i:s') . " | Order #{$orderId} | Error: " . $e->getMessage() . "\n",
            FILE_APPEND | LOCK_EX
        );

        // Also log failure to DB
        try {
            if (isset($db) && isset($customerEmail) && isset($html)) {
                $subject = "PrintingBruno - Confirmación de Pedido " . ($orderNumber ?? "#{$orderId}");
                $stmt = $db->prepare("INSERT INTO email_logs (order_id, recipient_email, subject, body_html, status, error_message) VALUES (?, ?, ?, ?, 'failed', ?)");
                $stmt->execute([$orderId, $customerEmail, $subject, $html, $e->getMessage()]);
            }
        } catch (\Exception $dbEx) {
            error_log("Failed to log email failure to DB: " . $dbEx->getMessage());
        }

        return false;
    }
}
