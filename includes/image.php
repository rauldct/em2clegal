<?php
/**
 * Procesamiento y redimensionado de imágenes
 */

require_once __DIR__ . '/config.php';

/**
 * Procesar imagen subida: validar, renombrar, crear tamaños
 */
function process_upload(array $file): array
{
    // Validar errores de upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir el archivo.'];
    }

    // Validar tamaño
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'El archivo supera el límite de 5MB.'];
    }

    // Validar MIME type real
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido. Solo JPG, PNG, WebP y GIF.'];
    }

    // Generar nombre aleatorio
    $ext = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg',
    };
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $year_month = date('Y/m');

    // Crear directorio
    $dir = UPLOADS_PATH . '/' . $year_month;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Mover archivo original
    $original_path = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $original_path)) {
        return ['success' => false, 'error' => 'No se pudo guardar el archivo.'];
    }

    // Crear tamaños redimensionados
    $sizes = [];
    foreach (IMAGE_SIZES as $size_name => $dimensions) {
        $resized_filename = pathinfo($filename, PATHINFO_FILENAME) . "-{$size_name}.{$ext}";
        $resized_path = $dir . '/' . $resized_filename;

        if (resize_image($original_path, $resized_path, $dimensions['width'], $dimensions['height'], $mime)) {
            $sizes[$size_name] = $year_month . '/' . $resized_filename;
        }
    }

    $filepath = $year_month . '/' . $filename;

    return [
        'success'  => true,
        'filename' => $filename,
        'filepath' => $filepath,
        'mime'     => $mime,
        'sizes'    => $sizes,
        'url'      => UPLOADS_URL . '/' . $filepath,
    ];
}

/**
 * Redimensionar imagen manteniendo proporción
 */
function resize_image(string $source, string $dest, int $max_width, int $max_height, string $mime): bool
{
    $info = getimagesize($source);
    if (!$info) return false;

    $orig_w = $info[0];
    $orig_h = $info[1];

    // No ampliar si ya es menor
    if ($orig_w <= $max_width && $orig_h <= $max_height) {
        return copy($source, $dest);
    }

    // Calcular nuevas dimensiones
    $ratio = min($max_width / $orig_w, $max_height / $orig_h);
    $new_w = (int)round($orig_w * $ratio);
    $new_h = (int)round($orig_h * $ratio);

    // Cargar imagen
    $src_img = match($mime) {
        'image/jpeg' => imagecreatefromjpeg($source),
        'image/png'  => imagecreatefrompng($source),
        'image/webp' => imagecreatefromwebp($source),
        'image/gif'  => imagecreatefromgif($source),
        default      => null,
    };
    if (!$src_img) return false;

    $dst_img = imagecreatetruecolor($new_w, $new_h);

    // Preservar transparencia para PNG
    if ($mime === 'image/png') {
        imagealphablending($dst_img, false);
        imagesavealpha($dst_img, true);
    }

    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);

    // Guardar
    $result = match($mime) {
        'image/jpeg' => imagejpeg($dst_img, $dest, 85),
        'image/png'  => imagepng($dst_img, $dest, 8),
        'image/webp' => imagewebp($dst_img, $dest, 85),
        'image/gif'  => imagegif($dst_img, $dest),
        default      => false,
    };

    imagedestroy($src_img);
    imagedestroy($dst_img);

    return $result;
}

/**
 * Eliminar imagen y sus tamaños
 */
function delete_image(string $filepath): void
{
    $full_path = UPLOADS_PATH . '/' . $filepath;
    if (file_exists($full_path)) {
        unlink($full_path);
    }

    // Eliminar tamaños redimensionados
    $info = pathinfo($full_path);
    foreach (array_keys(IMAGE_SIZES) as $size_name) {
        $sized = $info['dirname'] . '/' . $info['filename'] . "-{$size_name}." . $info['extension'];
        if (file_exists($sized)) {
            unlink($sized);
        }
    }
}
