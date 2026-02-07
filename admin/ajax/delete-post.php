<?php
/**
 * AJAX: Eliminar artículo
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

if (!auth_check()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($token) || !hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($input['id'] ?? 0);

if (!$post_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID no válido']);
    exit;
}

// Eliminar tags asociados
db()->prepare('DELETE FROM post_tags WHERE post_id = ?')->execute([$post_id]);
// Eliminar post
db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$post_id]);

echo json_encode(['success' => true]);
