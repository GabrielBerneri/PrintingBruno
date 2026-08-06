<?php
/**
 * PrintingBruno - Admin 2FA: enviar código por email
 * POST /api/admin/send_2fa.php
 * Llamado después de validar usuario/contraseña correctamente.
 * La sesión debe tener admin_pending_2fa = true.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/rate_limit.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

adminEnsureSessionStarted();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

if (empty($_SESSION['admin_pending_2fa']) || empty($_SESSION['admin_pending_id'])) {
    jsonResponse(['error' => 'No hay sesión pendiente de verificación'], 403);
}

$rateLimitKey = getRateLimitKey('admin_2fa_send');
checkAndIncrementRateLimit($rateLimitKey, 5, 600, 600, 'Demasiados intentos. Esperá 10 minutos.');

$db   = getDB();
$stmt = $db->prepare("SELECT id, username, email FROM admin_users WHERE id = ?");
$stmt->execute([(int)$_SESSION['admin_pending_id']]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(['error' => 'Usuario no encontrado'], 403);
}

$code      = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiresAt = time() + 300; // 5 minutos

$_SESSION['admin_2fa_code']       = password_hash($code, PASSWORD_DEFAULT);
$_SESSION['admin_2fa_expires_at'] = $expiresAt;
$_SESSION['admin_2fa_attempts']   = 0;

$recipientEmail = trim((string)($user['email'] ?? '')) ?: SMTP_USER;
$safeName       = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0a0a1a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
  <div style="max-width:520px;margin:0 auto;padding:32px 16px">
    <div style="text-align:center;padding:24px 0;border-bottom:2px solid #ff6b2b">
      <span style="font-size:1.5rem;font-weight:800;color:#ffffff">Printing<span style="color:#ff6b2b">Bruno</span></span>
    </div>
    <div style="background:#12122a;border-radius:12px;padding:28px;margin:24px 0;text-align:center">
      <p style="color:#c0c0d8;font-size:0.95rem;margin:0 0 24px">
        Hola <strong style="color:#fff">{$safeName}</strong>, alguien intentó ingresar al panel de administración.<br>
        Si sos vos, usá este código. Expira en <strong style="color:#ff6b2b">5 minutos</strong>.
      </p>
      <div style="background:#0a0a1a;border:2px solid #ff6b2b;border-radius:12px;padding:20px;display:inline-block;margin:0 auto">
        <span style="font-size:2.5rem;font-weight:900;letter-spacing:0.25em;color:#ff6b2b;font-family:monospace">{$code}</span>
      </div>
      <p style="color:#666;font-size:0.82rem;margin:20px 0 0">
        Si no fuiste vos, cambiá tu contraseña del panel de inmediato.
      </p>
    </div>
  </div>
</body>
</html>
HTML;

try {
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
    $mail->addAddress($recipientEmail, $user['username']);
    $mail->isHTML(true);
    $mail->Subject = 'PrintingBruno - Código de verificación del panel';
    $mail->Body    = $html;
    $mail->AltBody = "Tu código de verificación es: $code (expira en 5 minutos)";
    $mail->send();

    jsonResponse(['success' => true, 'email_hint' => substr($recipientEmail, 0, 3) . '***@' . explode('@', $recipientEmail)[1]]);
} catch (Exception $e) {
    error_log('2FA send error: ' . $e->getMessage());
    jsonResponse(['error' => 'No se pudo enviar el email. Intentá de nuevo.'], 500);
}
