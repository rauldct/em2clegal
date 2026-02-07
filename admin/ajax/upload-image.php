<?php
/**
 * AJAX: Subir imagen
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/image.php';

if (!auth_check()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se envió ninguna imagen']);
    exit;
}

$result = process_upload($_FILES['image']);

if (!$result['success']) {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
    exit;
}

// Guardar en BD
$alt_text = trim($_POST['alt_text'] ?? '');
$stmt = db()->prepare('INSERT INTO media (filename, filepath, alt_text, mime_type, size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([
    $result['filename'],
    $result['filepath'],
    $alt_text,
    $result['mime'],
    $_FILES['image']['size'],
    $_SESSION['user_id'],
]);

echo json_encode([
    'success' => true,
    'id'      => (int)db()->lastInsertId(),
    'url'     => $result['url'],
    'filepath' => $result['filepath'],
    'sizes'   => $result['sizes'],
]);
