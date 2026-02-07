<?php
/**
 * Download a backup file
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

auth_require();
auth_require_admin();

$token = $_GET['token'] ?? '';
if (!hash_equals(csrf_token(), $token)) {
    http_response_code(403);
    exit('Token inválido.');
}

$file = basename($_GET['file'] ?? '');
$backup_dir = '/var/www/emc2/backup';
$path = "$backup_dir/$file";

if (!$file || !file_exists($path) || strpos(realpath($path), realpath($backup_dir)) !== 0) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

// Send file for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-cache, must-revalidate');
readfile($path);
exit;
