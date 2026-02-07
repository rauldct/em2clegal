<?php
/**
 * Configuración general del CMS - EMC2 Legal Blog
 */

// Modo de desarrollo (cambiar a false en producción)
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Base de datos
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'emc2legal_blog');
define('DB_USER', 'root');
define('DB_PASS', 'Emc2Db#Pr0d2026!');
define('DB_CHARSET', 'utf8mb4');

// URLs
define('SITE_URL', 'https://emc2legal.com');
define('BLOG_URL', SITE_URL . '/blog');
define('ADMIN_URL', SITE_URL . '/admin');
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOADS_URL', ASSETS_URL . '/uploads');

// Rutas del sistema
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', __DIR__);
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('UPLOADS_PATH', ROOT_PATH . '/assets/uploads');

// Blog
define('BLOG_TITLE', 'Blog EMC2 Legal');
define('BLOG_DESCRIPTION', 'Artículos sobre extranjería, nacionalidad y derecho en España');
define('POSTS_PER_PAGE', 9);
define('RSS_ITEMS', 20);

// Imágenes
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('IMAGE_SIZES', [
    'thumb'  => ['width' => 300,  'height' => 200],
    'medium' => ['width' => 800,  'height' => 500],
    'large'  => ['width' => 1200, 'height' => 750],
]);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
]);

// Seguridad
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('SESSION_LIFETIME', 3600 * 8); // 8 horas
define('CSRF_TOKEN_NAME', 'csrf_token');

// Zona horaria
date_default_timezone_set('Europe/Madrid');

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
