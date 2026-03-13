<?php
/**
 * PrintingBruno - Sesión admin segura
 * Incluir ANTES de session_start() en todos los endpoints admin.
 *
 * Configura cookies con HttpOnly, Secure y SameSite=Strict para
 * proteger contra XSS (robo de cookie) y CSRF.
 */

session_name('pb_admin_session');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? 80) == 443;

session_set_cookie_params([
    'lifetime' => 3600 * 8,   // 8 horas (igual que ADMIN_SESSION_LIFETIME)
    'path'     => '/',
    'domain'   => '',          // usa el dominio actual automáticamente
    'secure'   => $isHttps,    // solo HTTPS en producción; funciona en HTTP local
    'httponly' => true,        // JS no puede leer la cookie → mitiga XSS
    'samesite' => 'Strict',    // bloquea envío en requests cross-site → mitiga CSRF
]);

session_start();
