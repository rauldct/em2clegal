<?php
/**
 * RSS Feed
 */
header('Content-Type: application/rss+xml; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$blog_title = get_setting('blog_title', BLOG_TITLE);
$blog_desc  = get_setting('blog_description', BLOG_DESCRIPTION);

$stmt = db()->prepare("SELECT p.*, u.name as author_name, c.name as category_name
    FROM posts p
    LEFT JOIN users u ON p.author_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'published'
    ORDER BY p.published_at DESC
    LIMIT ?");
$stmt->execute([RSS_ITEMS]);
$posts = $stmt->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0"
    xmlns:atom="http://www.w3.org/2005/Atom"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
    <title><?= htmlspecialchars($blog_title) ?></title>
    <link><?= BLOG_URL ?></link>
    <description><?= htmlspecialchars($blog_desc) ?></description>
    <language>es</language>
    <lastBuildDate><?= date('r') ?></lastBuildDate>
    <atom:link href="<?= BLOG_URL ?>/feed.xml" rel="self" type="application/rss+xml"/>
    <managingEditor>administrativo@emc2legal.com (EMC2 Legal)</managingEditor>

    <?php foreach ($posts as $post): ?>
    <item>
        <title><?= htmlspecialchars($post['title']) ?></title>
        <link><?= BLOG_URL . '/' . htmlspecialchars($post['slug']) ?></link>
        <guid isPermaLink="true"><?= BLOG_URL . '/' . htmlspecialchars($post['slug']) ?></guid>
        <pubDate><?= date('r', strtotime($post['published_at'])) ?></pubDate>
        <dc:creator><?= htmlspecialchars($post['author_name'] ?? 'EMC2 Legal') ?></dc:creator>
        <?php if ($post['category_name']): ?>
        <category><?= htmlspecialchars($post['category_name']) ?></category>
        <?php endif; ?>
        <description><![CDATA[<?= $post['excerpt'] ?: auto_excerpt($post['content']) ?>]]></description>
        <content:encoded><![CDATA[<?= $post['content'] ?>]]></content:encoded>
        <?php if ($post['featured_image']): ?>
        <enclosure url="<?= htmlspecialchars($post['featured_image']) ?>" type="image/jpeg"/>
        <?php endif; ?>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
