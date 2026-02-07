<?php
/**
 * AJAX: Autoguardado de artículos
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
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

// Verificar CSRF
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($token) || !hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($input['id'] ?? 0);
$title   = trim($input['title'] ?? '');
$content = $input['content'] ?? '';
$excerpt = trim($input['excerpt'] ?? '');

if (!$post_id || !$title) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

// Verificar que el post existe
$stmt = db()->prepare('SELECT id FROM posts WHERE id = ?');
$stmt->execute([$post_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Artículo no encontrado']);
    exit;
}

// Autoguardar
$content = sanitize_html($content);
$stmt = db()->prepare('UPDATE posts SET title = ?, content = ?, excerpt = ? WHERE id = ?');
$stmt->execute([$title, $content, $excerpt, $post_id]);

echo json_encode([
    'success' => true,
    'saved_at' => date('H:i:s'),
]);
