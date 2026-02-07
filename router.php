<?php
/**
 * Router for PHP built-in server (replaces .htaccess rewrite rules)
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files directly
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Blog routes
$routes = [
    '#^/blog/sitemap\.xml$#'                          => '/blog/sitemap.xml.php',
    '#^/blog/feed\.xml$#'                             => '/blog/feed.xml.php',
    '#^/blog/buscar$#'                                => '/blog/search.php',
    '#^/blog/categoria/([a-z0-9-]+)/page/([0-9]+)$#'  => '/blog/category.php?slug=$1&page=$2',
    '#^/blog/categoria/([a-z0-9-]+)$#'                => '/blog/category.php?slug=$1',
    '#^/blog/etiqueta/([a-z0-9-]+)/page/([0-9]+)$#'   => '/blog/tag.php?slug=$1&page=$2',
    '#^/blog/etiqueta/([a-z0-9-]+)$#'                 => '/blog/tag.php?slug=$1',
    '#^/blog/page/([0-9]+)$#'                         => '/blog/index.php?page=$1',
    '#^/blog/?$#'                                     => '/blog/index.php',
    '#^/blog/([a-z0-9-]+)$#'                          => '/blog/post.php?slug=$1',
];

foreach ($routes as $pattern => $target) {
    if (preg_match($pattern, $uri, $matches)) {
        $file = preg_replace($pattern, $target, $uri);
        $parts = explode('?', $file, 2);
        $script = __DIR__ . $parts[0];

        if (isset($parts[1])) {
            parse_str($parts[1], $params);
            $_GET = array_merge($_GET, $params);
        }

        require $script;
        return true;
    }
}

// Default: serve index files for directories
if (is_dir(__DIR__ . $uri)) {
    foreach (['index.php', 'index.html'] as $index) {
        $file = __DIR__ . rtrim($uri, '/') . '/' . $index;
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
}

// 404
http_response_code(404);
echo '404 Not Found';
return true;
