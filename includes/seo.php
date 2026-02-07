<?php
/**
 * SEO: Schema.org, meta tags, Open Graph
 */

require_once __DIR__ . '/functions.php';

/**
 * Generar meta tags para el <head>
 */
function seo_meta_tags(array $meta): string
{
    $title       = e($meta['title'] ?? BLOG_TITLE);
    $description = e($meta['description'] ?? BLOG_DESCRIPTION);
    $canonical   = e($meta['canonical'] ?? '');
    $robots      = $meta['robots'] ?? 'index, follow';
    $image       = e($meta['image'] ?? '');
    $type        = $meta['type'] ?? 'website';
    $published   = $meta['published'] ?? '';
    $modified    = $meta['modified'] ?? '';
    $author      = e($meta['author'] ?? 'EMC2 Legal');

    $html  = "<title>{$title}</title>\n";
    $html .= "    <meta name=\"description\" content=\"{$description}\">\n";
    $html .= "    <meta name=\"robots\" content=\"{$robots}\">\n";
    if ($canonical) {
        $html .= "    <link rel=\"canonical\" href=\"{$canonical}\">\n";
    }

    // Open Graph
    $html .= "    <meta property=\"og:title\" content=\"{$title}\">\n";
    $html .= "    <meta property=\"og:description\" content=\"{$description}\">\n";
    $html .= "    <meta property=\"og:type\" content=\"{$type}\">\n";
    $html .= "    <meta property=\"og:locale\" content=\"es_ES\">\n";
    $html .= "    <meta property=\"og:site_name\" content=\"EMC2 Legal Abogados\">\n";
    if ($canonical) {
        $html .= "    <meta property=\"og:url\" content=\"{$canonical}\">\n";
    }
    if ($image) {
        $html .= "    <meta property=\"og:image\" content=\"{$image}\">\n";
    }
    if ($published) {
        $html .= "    <meta property=\"article:published_time\" content=\"{$published}\">\n";
    }
    if ($modified) {
        $html .= "    <meta property=\"article:modified_time\" content=\"{$modified}\">\n";
    }

    // Twitter Cards
    $html .= "    <meta name=\"twitter:card\" content=\"summary_large_image\">\n";
    $html .= "    <meta name=\"twitter:title\" content=\"{$title}\">\n";
    $html .= "    <meta name=\"twitter:description\" content=\"{$description}\">\n";
    if ($image) {
        $html .= "    <meta name=\"twitter:image\" content=\"{$image}\">\n";
    }

    return $html;
}

/**
 * Schema.org BlogPosting JSON-LD
 */
function seo_blog_posting(array $post): string
{
    $wc = word_count($post['content'] ?? '');
    $image = !empty($post['featured_image']) ? $post['featured_image'] : '';

    $schema = [
        '@context'      => 'https://schema.org',
        '@type'         => 'BlogPosting',
        'headline'      => $post['title'],
        'description'   => $post['excerpt'] ?? auto_excerpt($post['content'] ?? ''),
        'datePublished' => $post['published_at'] ?? $post['created_at'],
        'dateModified'  => $post['updated_at'] ?? $post['published_at'] ?? $post['created_at'],
        'wordCount'     => $wc,
        'author'        => [
            '@type' => 'Person',
            'name'  => $post['author_name'] ?? 'EMC2 Legal',
        ],
        'publisher'     => [
            '@type' => 'Organization',
            'name'  => 'EMC2 Legal Abogados',
            'url'   => SITE_URL,
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id'   => BLOG_URL . '/' . $post['slug'],
        ],
    ];

    if ($image) {
        $schema['image'] = $image;
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/**
 * Schema.org BreadcrumbList JSON-LD
 */
function seo_breadcrumb_schema(array $items): string
{
    $list = [];
    foreach ($items as $i => $item) {
        $list[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $item['name'],
            'item'     => $item['url'] ?? '',
        ];
    }

    $schema = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $list,
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/**
 * Schema.org Blog (para la página de listado)
 */
function seo_blog_schema(): string
{
    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Blog',
        'name'        => BLOG_TITLE,
        'description' => BLOG_DESCRIPTION,
        'url'         => BLOG_URL,
        'publisher'   => [
            '@type' => 'Organization',
            'name'  => 'EMC2 Legal Abogados',
            'url'   => SITE_URL,
        ],
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}
