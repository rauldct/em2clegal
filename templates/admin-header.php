<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
auth_require();
$current_user = auth_user();
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Panel') ?> - EMC2 Legal CMS</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/admin.css?v=<?= filemtime(ROOT_PATH . '/assets/css/admin.css') ?>">
    <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body class="admin-body">

<aside class="admin-sidebar">
    <div class="sidebar-logo">
        <a href="<?= ADMIN_URL ?>/">EMC2 LEGAL <span>CMS</span></a>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= ADMIN_URL ?>/" class="<?= $current_page === 'index' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?= ADMIN_URL ?>/post-list.php" class="<?= in_array($current_page, ['post-list','post-editor']) ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i> Artículos
        </a>
        <a href="<?= ADMIN_URL ?>/categories.php" class="<?= $current_page === 'categories' ? 'active' : '' ?>">
            <i class="fas fa-folder"></i> Categorías
        </a>
        <a href="<?= ADMIN_URL ?>/tags.php" class="<?= $current_page === 'tags' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i> Etiquetas
        </a>
        <a href="<?= ADMIN_URL ?>/media.php" class="<?= $current_page === 'media' ? 'active' : '' ?>">
            <i class="fas fa-images"></i> Medios
        </a>
        <?php if ($current_user['role'] === 'admin'): ?>
        <a href="<?= ADMIN_URL ?>/users.php" class="<?= $current_page === 'users' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Usuarios
        </a>
        <a href="<?= ADMIN_URL ?>/settings.php" class="<?= $current_page === 'settings' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> Configuración
        </a>
        <a href="<?= ADMIN_URL ?>/backup.php" class="<?= $current_page === 'backup' ? 'active' : '' ?>">
            <i class="fas fa-database"></i> Backup
        </a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= BLOG_URL ?>" target="_blank"><i class="fas fa-external-link-alt"></i> Ver Blog</a>
        <a href="<?= ADMIN_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
    </div>
</aside>

<main class="admin-main">
    <header class="admin-topbar">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <div class="admin-user">
            <span><?= e($current_user['name']) ?></span>
            <small><?= e($current_user['role']) ?></small>
        </div>
    </header>
    <div class="admin-content">
        <?php
        $flash = flash_get();
        if ($flash):
        ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
