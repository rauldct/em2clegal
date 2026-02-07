<?php
/**
 * Blog: Búsqueda
 */
$query = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$seo = [
    'title'  => ($query ? 'Buscar: ' . $query : 'Buscar') . ' | Blog EMC2 Legal',
    'robots' => 'noindex, follow',
];

require_once __DIR__ . '/../templates/header.php';

$breadcrumbs = [
    ['name' => 'Inicio', 'url' => SITE_URL],
    ['name' => 'Blog', 'url' => BLOG_URL],
    ['name' => 'Buscar'],
];
require __DIR__ . '/../templates/breadcrumbs.php';

$posts = [];
$total = 0;
$pagination = paginate(0, POSTS_PER_PAGE, 1);

if ($query) {
    $search_term = "%$query%";
    $stmt = db()->prepare("SELECT COUNT(*) FROM posts WHERE status = 'published' AND (title LIKE ? OR content LIKE ? OR excerpt LIKE ?)");
    $stmt->execute([$search_term, $search_term, $search_term]);
    $total = (int)$stmt->fetchColumn();

    $per_page = (int)get_setting('posts_per_page', POSTS_PER_PAGE);
    $pagination = paginate($total, $per_page, $page);

    $stmt = db()->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.status = 'published' AND (p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)
        ORDER BY p.published_at DESC
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
    $stmt->execute([$search_term, $search_term, $search_term]);
    $posts = $stmt->fetchAll();
}
?>

<section class="blog-section">
    <div class="blog-header">
        <h1>Buscar en el blog</h1>
        <form action="<?= BLOG_URL ?>/buscar" method="GET" class="search-form search-form-lg">
            <input type="text" name="q" value="<?= e($query) ?>" placeholder="Escribe tu búsqueda..." required autofocus>
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <?php if ($query): ?>
        <p class="search-results-count"><?= $total ?> resultado<?= $total !== 1 ? 's' : '' ?> para &ldquo;<?= e($query) ?>&rdquo;</p>
    <?php endif; ?>

    <div class="blog-layout">
        <div class="blog-main">
            <?php if ($query && empty($posts)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No se encontraron resultados</h3>
                    <p>Intenta con otros términos de búsqueda.</p>
                </div>
            <?php elseif (!empty($posts)): ?>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                        <?php require __DIR__ . '/../templates/post-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <?= render_pagination($pagination, BLOG_URL . '/buscar?q=' . urlencode($query)) ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
