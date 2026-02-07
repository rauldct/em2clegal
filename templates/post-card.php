<?php
/**
 * Tarjeta de artículo para listados
 * Variables esperadas: $post (array con datos del artículo)
 */
$image = $post['featured_image'] ?: 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60';
?>
<article class="post-card">
    <a href="<?= BLOG_URL . '/' . e($post['slug']) ?>" class="post-card-image">
        <img src="<?= e($image) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
    </a>
    <div class="post-card-body">
        <?php if (!empty($post['category_name'])): ?>
            <a href="<?= BLOG_URL ?>/categoria/<?= e($post['category_slug'] ?? slugify($post['category_name'])) ?>" class="post-card-category">
                <?= e($post['category_name']) ?>
            </a>
        <?php endif; ?>
        <h3 class="post-card-title">
            <a href="<?= BLOG_URL . '/' . e($post['slug']) ?>"><?= e($post['title']) ?></a>
        </h3>
        <p class="post-card-excerpt"><?= e(truncate($post['excerpt'] ?: auto_excerpt($post['content'] ?? ''), 140)) ?></p>
        <div class="post-card-meta">
            <time datetime="<?= e($post['published_at'] ?? $post['created_at']) ?>">
                <i class="far fa-calendar-alt"></i> <?= format_date($post['published_at'] ?? $post['created_at'], 'short') ?>
            </time>
            <span><i class="far fa-clock"></i> <?= reading_time($post['content'] ?? '') ?> min de lectura</span>
        </div>
    </div>
</article>
