<?php
/**
 * PrintingBruno - Admin API: Version info
 * GET /api/admin/version.php
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../version_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$versionInfo = pbGetVersionInfo();
$versionInfo['environment'] = APP_ENV;

jsonResponse($versionInfo);
