<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/seo.php';
require_once __DIR__ . '/../includes/pagination.php';

$analytics_code = get_setting('analytics_code', '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= seo_meta_tags($seo ?? []) ?>

    <link rel="alternate" type="application/rss+xml" title="<?= e(get_setting('blog_title', BLOG_TITLE)) ?>" href="<?= BLOG_URL ?>/feed.xml">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/shared.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/blog.css">
    <?php if ($analytics_code): ?>
    <?= $analytics_code ?>
    <?php endif; ?>
    <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body>

<a href="https://wa.me/34614319595" class="whatsapp-float" target="_blank" title="Contactar con Abogado de Extranjería por WhatsApp" aria-label="WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>

<div class="top-bar">
    <div><i class="far fa-clock"></i> Lunes a Viernes: 9:00 - 19:00</div>
    <div><i class="fas fa-map-marker-alt"></i> Despacho en Madrid (España)</div>
</div>

<header>
    <div class="nav-container">
        <div class="logo">
            <a href="<?= SITE_URL ?>/" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 12px;">
                <img src="<?= SITE_URL ?>/logo-emc2legal.jpg" alt="EMC2 Legal Abogados" height="45">
                <div class="logo-text">EMC2 LEGAL <span>Abogados</span></div>
            </a>
        </div>
        <nav role="navigation">
            <ul>
                <li><a href="<?= SITE_URL ?>/#extranjeria">Extranjería</a></li>
                <li><a href="<?= SITE_URL ?>/#areas">Otras Áreas</a></li>
                <li><a href="<?= BLOG_URL ?>/" class="nav-active">Blog</a></li>
                <li><a href="<?= SITE_URL ?>/#equipo">Nosotros</a></li>
                <li><a href="<?= SITE_URL ?>/#contacto" class="btn-header">Pedir Cita</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
