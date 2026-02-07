<?php
/**
 * Blog: Listado por etiqueta
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$tag_slug = trim($_GET['slug'] ?? '');
if (!$tag_slug) { header('Location: ' . BLOG_URL); exit; }

$stmt = db()->prepare('SELECT * FROM tags WHERE slug = ? LIMIT 1');
$stmt->execute([$tag_slug]);
$tag = $stmt->fetch();

if (!$tag) {
    http_response_code(404);
    $seo = ['title' => 'Etiqueta no encontrada | EMC2 Legal', 'robots' => 'noindex'];
    require_once __DIR__ . '/../templates/header.php';
    echo '<section class="blog-section"><div class="no-results"><h1>Etiqueta no encontrada</h1><a href="' . BLOG_URL . '/" class="btn-primary">Volver al blog</a></div></section>';
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));

$seo = [
    'title'       => 'Etiqueta: ' . $tag['name'] . ' | Blog EMC2 Legal',
    'description'  => 'Artículos etiquetados con ' . $tag['name'] . ' en el blog de EMC2 Legal Abogados.',
    'canonical'    => BLOG_URL . '/etiqueta/' . $tag['slug'] . ($page > 1 ? "/page/$page" : ''),
];

require_once __DIR__ . '/../templates/header.php';

$breadcrumbs = [
    ['name' => 'Inicio', 'url' => SITE_URL],
    ['name' => 'Blog', 'url' => BLOG_URL],
    ['name' => 'Etiqueta: ' . $tag['name']],
];
require __DIR__ . '/../templates/breadcrumbs.php';

// Posts con esta etiqueta
$stmt = db()->prepare("SELECT COUNT(*) FROM posts p INNER JOIN post_tags pt ON p.id = pt.post_id WHERE p.status = 'published' AND pt.tag_id = ?");
$stmt->execute([$tag['id']]);
$total = (int)$stmt->fetchColumn();

$per_page = (int)get_setting('posts_per_page', POSTS_PER_PAGE);
$pagination = paginate($total, $per_page, $page);

$stmt = db()->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name
    FROM posts p
    INNER JOIN post_tags pt ON p.id = pt.post_id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.status = 'published' AND pt.tag_id = ?
    ORDER BY p.published_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute([$tag['id']]);
$posts = $stmt->fetchAll();
?>

<section class="blog-section">
    <div class="blog-header">
        <h1>Etiqueta: <?= e($tag['name']) ?></h1>
        <p><?= $total ?> artículo<?= $total !== 1 ? 's' : '' ?> con esta etiqueta</p>
    </div>

    <div class="blog-layout">
        <div class="blog-main">
            <?php if (empty($posts)): ?>
                <div class="no-results">
                    <h3>No hay artículos con esta etiqueta</h3>
                    <a href="<?= BLOG_URL ?>/" class="btn-primary">Ver todos los artículos</a>
                </div>
            <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                        <?php require __DIR__ . '/../templates/post-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <?= render_pagination($pagination, BLOG_URL . '/etiqueta/' . $tag['slug']) ?>
            <?php endif; ?>
        </div>

        <aside class="blog-sidebar">
            <div class="sidebar-widget">
                <h4>Buscar</h4>
                <form action="<?= BLOG_URL ?>/buscar" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Buscar artículos..." required>
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
