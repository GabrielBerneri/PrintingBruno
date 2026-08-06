<?php
// Script de un solo uso — BORRAR después de ejecutar
require_once __DIR__ . '/api/db.php';

$email = 'contacto@printingbruno.com';

$db   = getDB();
$stmt = $db->prepare("UPDATE admin_users SET email = ? WHERE username = 'gberneri'");
$stmt->execute([$email]);

echo 'OK: ' . $stmt->rowCount() . ' fila(s) actualizadas. Email: ' . htmlspecialchars($email);
