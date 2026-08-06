<?php
// Script de diagnóstico SMTP — BORRAR después de usar
require_once __DIR__ . '/api/config.php';

$user = SMTP_USER;
$host = SMTP_HOST;
$port = SMTP_PORT;
$passLen = strlen((string)SMTP_PASS);

// Mostrar solo los primeros 4 chars del usuario para no exponer el email completo
$userMasked = substr($user, 0, 4) . str_repeat('*', max(0, strlen($user) - 8)) . substr($user, -4);

echo "SMTP_HOST: $host\n";
echo "SMTP_PORT: $port\n";
echo "SMTP_USER (masked): $userMasked\n";
echo "SMTP_PASS length: $passLen chars\n";
echo "SMTP_FROM_NAME: " . SMTP_FROM_NAME . "\n";
