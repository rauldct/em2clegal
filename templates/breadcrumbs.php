<?php
/**
 * Migas de pan
 * Variable esperada: $breadcrumbs (array de ['name' => ..., 'url' => ...])
 */
if (empty($breadcrumbs)) return;
?>
<nav class="breadcrumbs" aria-label="Migas de pan">
    <ol itemscope itemtype="https://schema.org/BreadcrumbList">
        <?php foreach ($breadcrumbs as $i => $crumb): ?>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <?php if (isset($crumb['url']) && $i < count($breadcrumbs) - 1): ?>
                    <a itemprop="item" href="<?= e($crumb['url']) ?>"><span itemprop="name"><?= e($crumb['name']) ?></span></a>
                <?php else: ?>
                    <span itemprop="name"><?= e($crumb['name']) ?></span>
                <?php endif; ?>
                <meta itemprop="position" content="<?= $i + 1 ?>">
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
<?= seo_breadcrumb_schema($breadcrumbs) ?>
