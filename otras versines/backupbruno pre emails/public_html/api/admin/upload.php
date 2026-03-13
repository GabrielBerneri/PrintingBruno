<?php
/**
 * PrintingBruno - Admin API: Image Upload
 * POST /api/admin/upload.php  (multipart/form-data, field: "file")
 * Returns: { "url": "assets/productos/filename.ext" }
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db.php';

if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    // Check file was received without error
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite del servidor.',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario.',
            UPLOAD_ERR_PARTIAL    => 'El archivo fue subido parcialmente.',
            UPLOAD_ERR_NO_FILE    => 'No se envió ningún archivo.',
        ];
        $errCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        $errMsg  = $uploadErrors[$errCode] ?? 'Error al subir el archivo.';
        jsonResponse(['error' => $errMsg], 400);
    }

    $file = $_FILES['file'];

    // Validate size: max 2MB
    $maxBytes = 2 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        jsonResponse(['error' => 'El archivo supera el máximo permitido (2 MB).'], 400);
    }

    // Validate real MIME type via finfo — never trust $_FILES['type'] (client-controlled)
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!isset($allowed[$mime])) {
        jsonResponse(['error' => 'Tipo de archivo no permitido. Solo JPG, PNG, WebP o GIF.'], 400);
    }

    $ext      = $allowed[$mime];
    $filename = 'product-' . bin2hex(random_bytes(8)) . '.' . $ext;

    // Ensure upload directory exists (inside project)
    $uploadDir = __DIR__ . '/../../assets/productos/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            jsonResponse(['error' => 'No se pudo crear el directorio de imágenes.'], 500);
        }
    }

    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        error_log('upload: move_uploaded_file failed for ' . $filename);
        jsonResponse(['error' => 'Error al guardar el archivo.'], 500);
    }

    jsonResponse([
        'url'      => 'assets/productos/' . $filename,
        'filename' => $filename,
    ]);

} catch (Exception $e) {
    error_log('upload error: ' . $e->getMessage());
    jsonResponse(['error' => 'Error del servidor.'], 500);
}
