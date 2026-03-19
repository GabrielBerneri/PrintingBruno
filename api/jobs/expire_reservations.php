<?php
/**
 * Expires active stock reservations for stale orders.
 *
 * CLI: php api/jobs/expire_reservations.php
 * HTTP: GET|POST /api/jobs/expire_reservations.php
 * Header recomendado: X-Version-Token
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../order_utils.php';

if (PHP_SAPI !== 'cli') {
    $providedToken = (string)($_SERVER['HTTP_X_VERSION_TOKEN'] ?? $_GET['token'] ?? '');
    if (VERSION_TOKEN === '' || !hash_equals(VERSION_TOKEN, $providedToken)) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
}

try {
    $db = getDB();
    $expired = pbExpireReservations($db);
    jsonResponse(['success' => true, 'expired' => $expired]);
} catch (Throwable $e) {
    error_log('expire_reservations error: ' . $e->getMessage());
    jsonResponse(['error' => 'Server error'], 500);
}
