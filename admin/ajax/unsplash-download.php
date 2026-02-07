<?php
/**
 * Download an Unsplash image and save it to uploads
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

auth_require();
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$url = $input['url'] ?? '';

if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'error' => 'URL no válida.']);
    exit;
}

// Only allow Unsplash URLs
if (strpos($url, 'unsplash.com') === false) {
    echo json_encode(['success' => false, 'error' => 'Solo se permiten imágenes de Unsplash.']);
    exit;
}

// Download the image
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_USERAGENT => 'EMC2Legal-CMS/1.0',
]);
$image_data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($http_code !== 200 || !$image_data) {
    echo json_encode(['success' => false, 'error' => 'No se pudo descargar la imagen.']);
    exit;
}

// Determine extension from content type
$extensions = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

$ext = 'jpg'; // default for Unsplash
foreach ($extensions as $mime => $e) {
    if (strpos($content_type, $mime) !== false) {
        $ext = $e;
        break;
    }
}

// Create upload directory
$year_month = date('Y/m');
$upload_dir = UPLOADS_PATH . '/' . $year_month;
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$filename = 'unsplash-' . bin2hex(random_bytes(8)) . '.' . $ext;
$filepath = $upload_dir . '/' . $filename;

// Save the file
if (!file_put_contents($filepath, $image_data)) {
    echo json_encode(['success' => false, 'error' => 'No se pudo guardar la imagen.']);
    exit;
}

// Generate thumbnail and medium sizes
$sizes_created = [];
$base = pathinfo($filename, PATHINFO_FILENAME);

foreach (IMAGE_SIZES as $size_name => $dims) {
    $src = imagecreatefromstring($image_data);
    if (!$src) continue;

    $orig_w = imagesx($src);
    $orig_h = imagesy($src);
    $target_w = $dims['width'];
    $target_h = $dims['height'];

    // Calculate crop
    $ratio_w = $target_w / $orig_w;
    $ratio_h = $target_h / $orig_h;
    $ratio = max($ratio_w, $ratio_h);
    $new_w = (int)round($orig_w * $ratio);
    $new_h = (int)round($orig_h * $ratio);

    $resized = imagecreatetruecolor($target_w, $target_h);
    imagecopyresampled($resized, $src, 0, 0, (int)(($new_w - $target_w) / 2 / $ratio), (int)(($new_h - $target_h) / 2 / $ratio), $target_w, $target_h, (int)($target_w / $ratio), (int)($target_h / $ratio));

    $size_filename = $base . '-' . $size_name . '.' . $ext;
    $size_path = $upload_dir . '/' . $size_filename;

    if ($ext === 'png') {
        imagepng($resized, $size_path, 8);
    } else {
        imagejpeg($resized, $size_path, 85);
    }

    imagedestroy($src);
    imagedestroy($resized);
    $sizes_created[$size_name] = UPLOADS_URL . '/' . $year_month . '/' . $size_filename;
}

$full_url = UPLOADS_URL . '/' . $year_month . '/' . $filename;

// Save to media table
$stmt = db()->prepare('INSERT INTO media (filename, filepath, alt_text, mime_type, created_at) VALUES (?, ?, ?, ?, NOW())');
$stmt->execute([$filename, $year_month . '/' . $filename, 'Imagen de Unsplash', 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext)]);

echo json_encode([
    'success' => true,
    'url' => $full_url,
    'sizes' => $sizes_created,
]);
