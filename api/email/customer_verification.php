<?php
/**
 * Sends customer email verification emails.
 */

require_once __DIR__ . '/../customer_auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

function sendCustomerVerificationEmail(array $customer, string $token): bool
{
    $db = getDB();
    $recipientEmail = trim((string)($customer['email'] ?? ''));
    if ($recipientEmail === '') {
        return false;
    }

    $recipientName = trim(pbCustomerFormatFullName($customer)) ?: trim((string)($customer['first_name'] ?? 'Cliente'));
    $verificationUrl = rtrim(SITE_URL, '/') . '/mi-cuenta.php?verify=' . rawurlencode($token);
    $subject = 'PrintingBruno - Verificá tu cuenta';
    $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0a0a1a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
  <div style="max-width:600px;margin:0 auto;padding:32px 16px">
    <div style="text-align:center;padding:24px 0;border-bottom:2px solid #ff6b2b">
      <span style="font-size:1.5rem;font-weight:800;color:#ffffff">Printing<span style="color:#ff6b2b">Bruno</span></span>
    </div>
    <div style="background:#12122a;border-radius:12px;padding:28px;margin:24px 0">
      <p style="color:#ffffff;font-size:1.05rem;margin:0 0 12px">Hola <strong>{$safeName}</strong>,</p>
      <p style="color:#c0c0d8;font-size:0.95rem;line-height:1.7;margin:0 0 12px">
        Tu cuenta ya está creada. Solo falta verificar tu email para terminar de activarla y poder vincular pedidos hechos como invitado.
      </p>
      <p style="color:#c0c0d8;font-size:0.95rem;line-height:1.7;margin:0">
        El enlace expira en 72 horas.
      </p>
    </div>
    <div style="text-align:center;padding:12px 0 24px">
      <a href="{$safeUrl}" style="display:inline-block;background:#ff6b2b;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:700;font-size:0.95rem">
        Verificar mi cuenta
      </a>
    </div>
    <div style="background:#12122a;border-radius:12px;padding:20px;margin-bottom:20px">
      <p style="color:#8a8aab;font-size:0.85rem;line-height:1.6;margin:0 0 12px">Si el botón no funciona, copiá y pegá este enlace:</p>
      <p style="margin:0;word-break:break-all"><a href="{$safeUrl}" style="color:#ff6b2b;text-decoration:none">{$safeUrl}</a></p>
    </div>
  </div>
</body>
</html>
HTML;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = "Hola {$recipientName}. Verificá tu cuenta con este enlace: {$verificationUrl}";
        $mail->send();

        $stmt = $db->prepare("INSERT INTO email_logs (recipient_email, subject, body_html, status) VALUES (?, ?, ?, 'sent')");
        $stmt->execute([$recipientEmail, $subject, $html]);
        return true;
    } catch (Throwable $e) {
        error_log('customer_verification_email_error: ' . $e->getMessage());
        try {
            $stmt = $db->prepare("INSERT INTO email_logs (recipient_email, subject, body_html, status, error_message) VALUES (?, ?, ?, 'failed', ?)");
            $stmt->execute([$recipientEmail, $subject, $html, $e->getMessage()]);
        } catch (Throwable $logError) {
            error_log('customer_verification_email_log_failure: ' . $logError->getMessage());
        }
        return false;
    }
}
