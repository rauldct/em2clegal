<?php
/**
 * AJAX: Verificar disponibilidad de slug
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!auth_check()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$slug = trim($_GET['slug'] ?? '');
$exclude_id = (int)($_GET['exclude'] ?? 0);

if (!$slug) {
    echo json_encode(['available' => false]);
    exit;
}

$exists = slug_exists($slug, 'posts', $exclude_id ?: null);
$suggestion = $exists ? unique_slug($slug, 'posts', $exclude_id ?: null) : $slug;

echo json_encode([
    'available'  => !$exists,
    'suggestion' => $suggestion,
]);
