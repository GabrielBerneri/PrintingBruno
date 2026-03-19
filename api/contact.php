<?php
/**
 * PrintingBruno - API: Contact Form Handler
 * POST /api/contact.php
 * Receives contact form data and sends it via SMTP to both email addresses.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// Catch fatal errors and output as JSON (never expose internal details)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('contact_fatal: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Error interno del servidor.']);
        exit;
    }
});

// Catch all warnings/notices as exceptions
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require_once __DIR__ . '/db.php';             // Includes config.php + jsonResponse()
    require_once __DIR__ . '/security/rate_limit.php';
    require_once __DIR__ . '/../vendor/autoload.php';

    // Handle CORS preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $name    = trim($input['name'] ?? '');
    $email   = trim($input['email'] ?? '');
    $phone   = trim($input['phone'] ?? '');
    $quantity = trim($input['quantity'] ?? '');
    $timeline = trim($input['timeline'] ?? '');
    $subject = trim($input['subject'] ?? '');
    $message = trim($input['message'] ?? '');
    $website = trim($input['website'] ?? '');

    // Honeypot: bots usually fill hidden fields. Respond as success without sending anything.
    if ($website !== '') {
        jsonResponse(['success' => true, 'message' => '¡Consulta enviada! Te responderemos a la brevedad.']);
    }

    if (empty($name) || empty($email) || empty($message)) {
        jsonResponse(['error' => 'Nombre, email y mensaje son obligatorios.'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'El email no es válido.'], 400);
    }

    if (mb_strlen($message) < 10) {
        jsonResponse(['error' => 'Contanos un poco más sobre tu consulta para poder ayudarte mejor.'], 400);
    }

    checkAndIncrementRateLimit(
        getRateLimitKey('contact'),
        3,
        3600,
        3600,
        'Demasiados mensajes enviados. Intentá nuevamente en una hora.'
    );

    // Subject label mapping
    $subjectLabels = [
        'presupuesto'   => 'Presupuesto',
        'personalizado' => 'Diseño personalizado',
        'archivo'       => 'Tengo archivo STL',
        'mayorista'     => 'Consulta mayorista',
        'otro'          => 'Otro',
    ];
    $subjectLabel = $subjectLabels[$subject] ?? ($subject ?: 'Sin especificar');

    $timelineLabels = [
        'esta-semana'    => 'Lo necesito esta semana',
        'proxima-semana' => 'Lo necesito la próxima semana',
        'este-mes'       => 'Lo necesito este mes',
        'cotizar'        => 'Solo quiere cotizar',
    ];
    $timelineLabel = $timelineLabels[$timeline] ?? ($timeline ?: 'Flexible / sin apuro');

    $detailRows = '';
    if ($quantity !== '') {
        $detailRows .= '
            <tr>
              <td style="padding:12px 16px; color:#999; font-size:13px; border-bottom:1px solid #2a2a2a;">Cantidad estimada</td>
              <td style="padding:12px 16px; color:#f0f0f0; font-size:15px; border-bottom:1px solid #2a2a2a;">' . htmlspecialchars($quantity) . '</td>
            </tr>';
    }
    if ($timelineLabel !== '') {
        $detailRows .= '
            <tr>
              <td style="padding:12px 16px; color:#999; font-size:13px; border-bottom:1px solid #2a2a2a;">Urgencia</td>
              <td style="padding:12px 16px; color:#f0f0f0; font-size:15px; border-bottom:1px solid #2a2a2a;">' . htmlspecialchars($timelineLabel) . '</td>
            </tr>';
    }

    // Build HTML email
    $htmlBody = '
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"></head>
    <body style="margin:0; padding:0; background:#0d0d0d; font-family:Arial,sans-serif;">
      <div style="max-width:600px; margin:0 auto; background:#1a1a1a; border-radius:12px; overflow:hidden; border:1px solid #2a2a2a;">
        <div style="background:linear-gradient(135deg, #ff6b35, #e85d26); padding:24px; text-align:center;">
          <h1 style="color:#fff; margin:0; font-size:22px;">📬 Nueva Consulta del Sitio Web</h1>
        </div>
        <div style="padding:32px;">
          <table style="width:100%; border-collapse:collapse;">
            <tr>
              <td style="padding:12px 16px; color:#999; font-size:13px; border-bottom:1px solid #2a2a2a; width:140px;">Nombre</td>
              <td style="padding:12px 16px; color:#f0f0f0; font-size:15px; border-bottom:1px solid #2a2a2a;"><strong>' . htmlspecialchars($name) . '</strong></td>
            </tr>
            <tr>
              <td style="padding:12px 16px; color:#999; font-size:13px; border-bottom:1px solid #2a2a2a;">Email</td>
              <td style="padding:12px 16px; border-bottom:1px solid #2a2a2a;">
                <a href="mailto:' . htmlspecialchars($email) . '" style="color:#ff6b35; text-decoration:none;">' . htmlspecialchars($email) . '</a>
              </td>
            </tr>
            <tr>
              <td style="padding:12px 16px; color:#999; font-size:13px; border-bottom:1px solid #2a2a2a;">Teléfono</td>
              <td style="padding:12px 16px; color:#f0f0f0; font-size:15px; border-bottom:1px solid #2a2a2a;">' . htmlspecialchars($phone ?: 'No proporcionado') . '</td>
            </tr>
            <tr>
              <td style="padding:12px 16px; color:#999; font-size:13px; border-bottom:1px solid #2a2a2a;">Tipo de Consulta</td>
              <td style="padding:12px 16px; color:#f0f0f0; font-size:15px; border-bottom:1px solid #2a2a2a;">' . htmlspecialchars($subjectLabel) . '</td>
            </tr>
            ' . $detailRows . '
          </table>
          <div style="margin-top:24px; padding:20px; background:#0d0d0d; border-radius:8px; border:1px solid #2a2a2a;">
            <p style="color:#999; font-size:12px; margin:0 0 8px 0; text-transform:uppercase; letter-spacing:1px;">Mensaje</p>
            <p style="color:#f0f0f0; font-size:15px; line-height:1.6; margin:0; white-space:pre-wrap;">' . htmlspecialchars($message) . '</p>
          </div>
          <div style="text-align:center; margin-top:24px;">
            <a href="mailto:' . htmlspecialchars($email) . '?subject=Re: Consulta PrintingBruno - ' . htmlspecialchars($subjectLabel) . '" 
               style="display:inline-block; padding:12px 32px; background:#ff6b35; color:#fff; text-decoration:none; border-radius:8px; font-weight:bold; font-size:14px;">
              Responder al cliente
            </a>
          </div>
        </div>
        <div style="padding:16px; text-align:center; border-top:1px solid #2a2a2a;">
          <p style="color:#666; font-size:12px; margin:0;">Enviado desde el formulario de contacto de printingbruno.com</p>
        </div>
      </div>
    </body>
    </html>';

    // Send email via PHPMailer
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
    $mail->addReplyTo($email, $name);

    // Send to both email addresses
    $mail->addAddress('contacto@printingbruno.com', 'PrintingBruno');
    $mail->addAddress('printingbruno.22@gmail.com', 'PrintingBruno Gmail');

    // Strip CRLFs from name/subject to prevent email header injection
    $safeSubjectName = str_replace(["\r", "\n", "\r\n"], '', $name);
    $mail->isHTML(true);
    $mail->Subject = "Nueva consulta: $subjectLabel - $safeSubjectName";
    $mail->Body    = $htmlBody;
    $mail->AltBody = "Nueva consulta de $name ($email)\nTipo: $subjectLabel\nTeléfono: " . ($phone ?: 'N/A') . "\nCantidad: " . ($quantity ?: 'N/A') . "\nUrgencia: $timelineLabel\n\nMensaje:\n$message";

    $mail->send();

    // Log the contact email
    try {
        $db = getDB();
        $db->prepare("INSERT INTO email_logs (recipient_email, subject, body_html, status) VALUES (?, ?, ?, 'sent')")
           ->execute(['contacto@printingbruno.com', $mail->Subject, $htmlBody]);
    } catch (\Exception $logErr) {
        error_log('contact_log_error: ' . $logErr->getMessage());
    }

    jsonResponse(['success' => true, 'message' => '¡Consulta enviada! Te responderemos a la brevedad.']);

} catch (\Exception $e) {
    error_log('contact_form_error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Error al enviar el mensaje. Por favor intentá nuevamente.']);
    exit;
} catch (\Error $e) {
    error_log('contact_form_fatal: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Error interno del servidor.']);
    exit;
}
