<?php
/**
 * Blog: Listado por categoría
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$cat_slug = trim($_GET['slug'] ?? '');
if (!$cat_slug) { header('Location: ' . BLOG_URL); exit; }

$stmt = db()->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
$stmt->execute([$cat_slug]);
$category = $stmt->fetch();

if (!$category) {
    http_response_code(404);
    $seo = ['title' => 'Categoría no encontrada | EMC2 Legal', 'robots' => 'noindex'];
    require_once __DIR__ . '/../templates/header.php';
    echo '<section class="blog-section"><div class="no-results"><h1>Categoría no encontrada</h1><a href="' . BLOG_URL . '/" class="btn-primary">Volver al blog</a></div></section>';
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));

$seo = [
    'title'       => ($category['meta_title'] ?: $category['name'] . ' | Blog EMC2 Legal'),
    'description'  => $category['meta_description'] ?: 'Artículos sobre ' . $category['name'] . ' en el blog de EMC2 Legal Abogados.',
    'canonical'    => BLOG_URL . '/categoria/' . $category['slug'] . ($page > 1 ? "/page/$page" : ''),
];

require_once __DIR__ . '/../templates/header.php';

$breadcrumbs = [
    ['name' => 'Inicio', 'url' => SITE_URL],
    ['name' => 'Blog', 'url' => BLOG_URL],
    ['name' => $category['name']],
];
require __DIR__ . '/../templates/breadcrumbs.php';

// Posts de la categoría
$total = (int)db()->prepare("SELECT COUNT(*) FROM posts WHERE status = 'published' AND category_id = ?")->execute([$category['id']]) ?
    db()->prepare("SELECT COUNT(*) FROM posts WHERE status = 'published' AND category_id = ?") : null;
$stmt = db()->prepare("SELECT COUNT(*) FROM posts WHERE status = 'published' AND category_id = ?");
$stmt->execute([$category['id']]);
$total = (int)$stmt->fetchColumn();

$per_page = (int)get_setting('posts_per_page', POSTS_PER_PAGE);
$pagination = paginate($total, $per_page, $page);

$stmt = db()->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.status = 'published' AND p.category_id = ?
    ORDER BY p.published_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute([$category['id']]);
$posts = $stmt->fetchAll();
?>

<section class="blog-section">
    <div class="blog-header">
        <h1><?= e($category['name']) ?></h1>
        <?php if ($category['description']): ?>
            <p><?= e($category['description']) ?></p>
        <?php endif; ?>
    </div>

    <div class="blog-layout">
        <div class="blog-main">
            <?php if (empty($posts)): ?>
                <div class="no-results">
                    <h3>No hay artículos en esta categoría</h3>
                    <p>Pronto publicaremos contenido sobre <?= e($category['name']) ?>.</p>
                    <a href="<?= BLOG_URL ?>/" class="btn-primary">Ver todos los artículos</a>
                </div>
            <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                        <?php require __DIR__ . '/../templates/post-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <?= render_pagination($pagination, BLOG_URL . '/categoria/' . $category['slug']) ?>
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
            <div class="sidebar-widget sidebar-cta">
                <h4>¿Necesitas un abogado?</h4>
                <p>Primera consulta gratuita.</p>
                <a href="<?= SITE_URL ?>/#contacto" class="btn-primary">Pedir cita</a>
            </div>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
