<?php
/**
 * Blog: Listado de artículos con paginación
 */
require_once __DIR__ . '/../includes/config.php';
$page = max(1, (int)($_GET['page'] ?? 1));

// SEO
$seo = [
    'title'       => 'Blog | EMC2 Legal Abogados - Artículos de Extranjería y Derecho',
    'description'  => 'Artículos sobre extranjería, nacionalidad española, arraigo, visados y derecho en España. Información legal actualizada por abogados expertos.',
    'canonical'    => BLOG_URL . ($page > 1 ? "/page/$page" : ''),
    'type'         => 'website',
];

require_once __DIR__ . '/../templates/header.php';

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Inicio', 'url' => SITE_URL],
    ['name' => 'Blog'],
];
require __DIR__ . '/../templates/breadcrumbs.php';

// Contar total de posts publicados
$total = (int)db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
$per_page = (int)get_setting('posts_per_page', POSTS_PER_PAGE);
$pagination = paginate($total, $per_page, $page);

// Obtener posts
$stmt = db()->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.status = 'published'
    ORDER BY p.published_at DESC
    LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// Categorías para sidebar
$categories = db()->query("SELECT c.*, (SELECT COUNT(*) FROM posts WHERE category_id = c.id AND status = 'published') as post_count FROM categories c ORDER BY name")->fetchAll();
?>

<?= seo_blog_schema() ?>

<section class="blog-section">
    <div class="blog-header">
        <h1>Blog de EMC2 Legal</h1>
        <p>Artículos sobre extranjería, nacionalidad y derecho en España</p>
    </div>

    <div class="blog-layout">
        <div class="blog-main">
            <?php if (empty($posts)): ?>
                <div class="no-results">
                    <i class="fas fa-file-alt"></i>
                    <h3>Aún no hay artículos publicados</h3>
                    <p>Pronto publicaremos contenido. ¡Vuelve pronto!</p>
                </div>
            <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                        <?php require __DIR__ . '/../templates/post-card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <?= render_pagination($pagination, BLOG_URL) ?>
            <?php endif; ?>
        </div>

        <aside class="blog-sidebar">
            <!-- Búsqueda -->
            <div class="sidebar-widget">
                <h4>Buscar</h4>
                <form action="<?= BLOG_URL ?>/buscar" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Buscar artículos..." required>
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <!-- Categorías -->
            <div class="sidebar-widget">
                <h4>Categorías</h4>
                <ul class="categories-list">
                    <?php foreach ($categories as $cat): ?>
                        <?php if ($cat['post_count'] > 0): ?>
                        <li>
                            <a href="<?= BLOG_URL ?>/categoria/<?= e($cat['slug']) ?>">
                                <?= e($cat['name']) ?>
                                <span class="count"><?= $cat['post_count'] ?></span>
                            </a>
                        </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- CTA -->
            <div class="sidebar-widget sidebar-cta">
                <h4>¿Necesitas un abogado?</h4>
                <p>Primera consulta gratuita. Expertos en extranjería y nacionalidad.</p>
                <a href="<?= SITE_URL ?>/#contacto" class="btn-primary">Pedir cita</a>
            </div>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
