<?php
/**
 * Sitemap XML dinámico - Optimizado para Google Search y AI crawlers
 */
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    <!-- Página principal -->
    <url>
        <loc><?= SITE_URL ?>/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Blog -->
    <url>
        <loc><?= BLOG_URL ?>/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Categorías con posts -->
<?php
$categories = db()->query("
    SELECT c.slug, c.name, MAX(p.updated_at) as last_updated
    FROM categories c
    INNER JOIN posts p ON p.category_id = c.id AND p.status = 'published'
    GROUP BY c.id, c.slug, c.name
    ORDER BY c.name
")->fetchAll();
foreach ($categories as $cat):
    $lastmod = $cat['last_updated'] ? date('Y-m-d', strtotime($cat['last_updated'])) : date('Y-m-d');
?>
    <url>
        <loc><?= BLOG_URL ?>/categoria/<?= htmlspecialchars($cat['slug']) ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>

    <!-- Etiquetas con posts -->
<?php
$tags = db()->query("
    SELECT t.slug, t.name, MAX(p.updated_at) as last_updated
    FROM tags t
    INNER JOIN post_tags pt ON pt.tag_id = t.id
    INNER JOIN posts p ON p.id = pt.post_id AND p.status = 'published'
    GROUP BY t.id, t.slug, t.name
    ORDER BY t.name
")->fetchAll();
foreach ($tags as $tag):
    $lastmod = $tag['last_updated'] ? date('Y-m-d', strtotime($tag['last_updated'])) : date('Y-m-d');
?>
    <url>
        <loc><?= BLOG_URL ?>/etiqueta/<?= htmlspecialchars($tag['slug']) ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
<?php endforeach; ?>

    <!-- Artículos publicados -->
<?php
$posts = db()->query("
    SELECT slug, title, featured_image, published_at, updated_at
    FROM posts
    WHERE status = 'published'
    ORDER BY published_at DESC
")->fetchAll();
foreach ($posts as $post):
    $lastmod = date('Y-m-d', strtotime($post['updated_at'] ?: $post['published_at']));
?>
    <url>
        <loc><?= BLOG_URL ?>/<?= htmlspecialchars($post['slug']) ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
<?php if (!empty($post['featured_image'])): ?>
        <image:image>
            <image:loc><?= htmlspecialchars($post['featured_image']) ?></image:loc>
            <image:title><?= htmlspecialchars($post['title']) ?></image:title>
        </image:image>
<?php endif; ?>
    </url>
<?php endforeach; ?>

    <!-- Feed RSS -->
    <url>
        <loc><?= BLOG_URL ?>/feed.xml</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.3</priority>
    </url>
</urlset>
