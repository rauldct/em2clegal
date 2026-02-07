<?php
/**
 * Blog: Artículo individual
 */
$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    http_response_code(404);
    exit('Artículo no encontrado.');
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Buscar artículo
$stmt = db()->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.slug = ? AND p.status = 'published'
    LIMIT 1");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $seo = ['title' => 'Artículo no encontrado | EMC2 Legal', 'robots' => 'noindex'];
    require_once __DIR__ . '/../templates/header.php';
    echo '<section class="blog-section"><div class="no-results"><h1>Artículo no encontrado</h1><p>El artículo que buscas no existe o ha sido retirado.</p><a href="' . BLOG_URL . '/" class="btn-primary">Volver al blog</a></div></section>';
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

// Incrementar visitas
db()->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$post['id']]);

// Tags del artículo
$stmt = db()->prepare('SELECT t.* FROM tags t INNER JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?');
$stmt->execute([$post['id']]);
$tags = $stmt->fetchAll();

// Artículos relacionados
$related = [];
if ($post['category_id']) {
    $stmt = db()->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'published' AND p.id != ? AND p.category_id = ?
        ORDER BY p.published_at DESC LIMIT 3");
    $stmt->execute([$post['id'], $post['category_id']]);
    $related = $stmt->fetchAll();
}

// SEO
$seo = [
    'title'       => $post['meta_title'] ?: ($post['title'] . ' | EMC2 Legal'),
    'description'  => $post['meta_description'] ?: auto_excerpt($post['content']),
    'canonical'    => BLOG_URL . '/' . $post['slug'],
    'image'        => $post['featured_image'] ?: '',
    'type'         => 'article',
    'published'    => $post['published_at'],
    'modified'     => $post['updated_at'],
    'author'       => $post['author_name'],
];

require_once __DIR__ . '/../templates/header.php';

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Inicio', 'url' => SITE_URL],
    ['name' => 'Blog', 'url' => BLOG_URL],
];
if ($post['category_name']) {
    $breadcrumbs[] = ['name' => $post['category_name'], 'url' => BLOG_URL . '/categoria/' . $post['category_slug']];
}
$breadcrumbs[] = ['name' => $post['title']];
require __DIR__ . '/../templates/breadcrumbs.php';
?>

<?= seo_blog_posting($post) ?>

<section class="blog-section">
    <div class="blog-layout">
        <div class="blog-main">
            <article class="post-single" itemscope itemtype="https://schema.org/BlogPosting">
                <?php if ($post['featured_image']): ?>
                <div class="post-featured-image">
                    <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" itemprop="image">
                </div>
                <?php endif; ?>

                <div class="post-header">
                    <?php if ($post['category_name']): ?>
                        <a href="<?= BLOG_URL ?>/categoria/<?= e($post['category_slug']) ?>" class="post-category" itemprop="articleSection">
                            <?= e($post['category_name']) ?>
                        </a>
                    <?php endif; ?>

                    <h1 itemprop="headline"><?= e($post['title']) ?></h1>

                    <div class="post-meta">
                        <span itemprop="author" itemscope itemtype="https://schema.org/Person">
                            <i class="far fa-user"></i> <span itemprop="name"><?= e($post['author_name']) ?></span>
                        </span>
                        <time datetime="<?= e($post['published_at']) ?>" itemprop="datePublished">
                            <i class="far fa-calendar-alt"></i> <?= format_date($post['published_at']) ?>
                        </time>
                        <span><i class="far fa-clock"></i> <?= reading_time($post['content']) ?> min de lectura</span>
                        <meta itemprop="dateModified" content="<?= e($post['updated_at']) ?>">
                        <meta itemprop="wordCount" content="<?= word_count($post['content']) ?>">
                    </div>
                </div>

                <!-- Barra de progreso de lectura -->
                <div class="reading-progress" id="readingProgress"></div>

                <div class="post-content" itemprop="articleBody">
                    <?= $post['content'] ?>
                </div>

                <?php if (!empty($tags)): ?>
                <div class="post-tags">
                    <i class="fas fa-tags"></i>
                    <?php foreach ($tags as $tag): ?>
                        <a href="<?= BLOG_URL ?>/etiqueta/<?= e($tag['slug']) ?>" class="tag" rel="tag"><?= e($tag['name']) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Compartir -->
                <div class="post-share">
                    <span>Compartir:</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(BLOG_URL . '/' . $post['slug']) ?>" target="_blank" rel="noopener" title="Compartir en Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode(BLOG_URL . '/' . $post['slug']) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" rel="noopener" title="Compartir en Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(BLOG_URL . '/' . $post['slug']) ?>" target="_blank" rel="noopener" title="Compartir en LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' ' . BLOG_URL . '/' . $post['slug']) ?>" target="_blank" rel="noopener" title="Compartir por WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </article>

            <?php if (!empty($related)): ?>
            <section class="related-posts">
                <h3>Artículos relacionados</h3>
                <div class="posts-grid posts-grid-3">
                    <?php foreach ($related as $post): ?>
                        <?php require __DIR__ . '/../templates/post-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </section>
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
                <p>Primera consulta gratuita. Expertos en extranjería y nacionalidad.</p>
                <a href="<?= SITE_URL ?>/#contacto" class="btn-primary">Pedir cita</a>
            </div>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
