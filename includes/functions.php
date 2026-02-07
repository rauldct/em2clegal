<?php
/**
 * Utilidades generales
 */

/**
 * Generar slug a partir de texto
 */
function slugify(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    // Reemplazar caracteres especiales del español
    $replacements = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ñ' => 'n', 'ü' => 'u', 'ç' => 'c',
    ];
    $text = str_replace(array_keys($replacements), array_values($replacements), $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Escape HTML seguro
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Truncar texto a X caracteres
 */
function truncate(string $text, int $length = 160, string $suffix = '...'): string
{
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Formatear fecha en español
 */
function format_date(string $date, string $format = 'long'): string
{
    $months = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    $ts = strtotime($date);
    $day   = date('j', $ts);
    $month = $months[(int)date('n', $ts)];
    $year  = date('Y', $ts);

    if ($format === 'short') {
        return "$day " . mb_substr($month, 0, 3) . " $year";
    }
    return "$day de $month de $year";
}

/**
 * Tiempo relativo (hace X minutos/horas)
 */
function time_ago(string $date): string
{
    $diff = time() - strtotime($date);
    if ($diff < 60)    return 'hace un momento';
    if ($diff < 3600)  return 'hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400) return 'hace ' . floor($diff / 3600) . ' horas';
    if ($diff < 604800) return 'hace ' . floor($diff / 86400) . ' días';
    return format_date($date, 'short');
}

/**
 * Contar palabras de un texto HTML
 */
function word_count(string $html): int
{
    $text = strip_tags($html);
    return str_word_count($text);
}

/**
 * Tiempo de lectura estimado
 */
function reading_time(string $html): int
{
    return max(1, (int)ceil(word_count($html) / 200));
}

/**
 * Generar extracto automático si no hay uno manual
 */
function auto_excerpt(string $html, int $length = 160): string
{
    $text = strip_tags($html);
    $text = preg_replace('/\s+/', ' ', $text);
    return truncate(trim($text), $length);
}

/**
 * Sanitizar contenido HTML del editor (whitelist de tags)
 */
function sanitize_html(string $html): string
{
    $allowed = '<p><br><strong><b><em><i><u><s><a><ul><ol><li><h2><h3><h4><h5><h6>'
             . '<blockquote><pre><code><img><figure><figcaption><table><thead><tbody>'
             . '<tr><th><td><hr><div><span><iframe>';
    return strip_tags($html, $allowed);
}

/**
 * Verificar si un slug ya existe
 */
function slug_exists(string $slug, string $table = 'posts', ?int $exclude_id = null): bool
{
    $sql = "SELECT COUNT(*) FROM `$table` WHERE slug = ?";
    $params = [$slug];
    if ($exclude_id) {
        $sql .= ' AND id != ?';
        $params[] = $exclude_id;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Generar slug único
 */
function unique_slug(string $text, string $table = 'posts', ?int $exclude_id = null): string
{
    $slug = slugify($text);
    if (!slug_exists($slug, $table, $exclude_id)) return $slug;

    $i = 2;
    while (slug_exists("$slug-$i", $table, $exclude_id)) {
        $i++;
    }
    return "$slug-$i";
}

/**
 * Redirigir
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/**
 * Mensaje flash (se muestra una vez)
 */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Obtener configuración del blog desde BD
 */
function get_setting(string $key, string $default = ''): string
{
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];

    try {
        $stmt = db()->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        $cache[$key] = $val !== false ? $val : $default;
    } catch (Exception $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

/**
 * Establecer configuración
 */
function set_setting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?');
    $stmt->execute([$key, $value, $value]);
}

/**
 * Notificar a buscadores cuando se publica/actualiza un artículo.
 * Usa IndexNow (Bing, Yandex, Naver, Seznam) para indexación instantánea.
 * Para Google: el sitemap + Search Console se encarga automáticamente.
 */
function ping_search_engines(string $post_url = ''): void
{
    if (!$post_url) return;

    $host = parse_url(SITE_URL, PHP_URL_HOST);
    $key_file = ROOT_PATH . '/indexnow-key.txt';

    // Generar clave IndexNow si no existe
    if (!file_exists($key_file)) {
        $key = bin2hex(random_bytes(16));
        @file_put_contents($key_file, $key);
        // Crear archivo de verificación
        @file_put_contents(ROOT_PATH . '/' . $key . '.txt', $key);
    }
    $key = trim(@file_get_contents($key_file));
    if (!$key) return;

    // IndexNow: indexación instantánea en Bing, Yandex, etc.
    $payload = json_encode([
        'host' => $host,
        'key' => $key,
        'keyLocation' => SITE_URL . '/' . $key . '.txt',
        'urlList' => [
            $post_url,
            BLOG_URL . '/',
            BLOG_URL . '/sitemap.xml',
        ],
    ]);

    $engines = [
        'https://api.indexnow.org/indexnow',
        'https://www.bing.com/indexnow',
        'https://yandex.com/indexnow',
    ];

    foreach ($engines as $endpoint) {
        @file_get_contents($endpoint, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json; charset=utf-8\r\n",
                'content' => $payload,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]));
    }
}
