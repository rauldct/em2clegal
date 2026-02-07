<?php
$page_title = 'Artículos';
require_once __DIR__ . '/../templates/admin-header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/pagination.php';

// Filtros
$status_filter   = $_GET['status'] ?? '';
$category_filter = (int)($_GET['category'] ?? 0);
$search_filter   = trim($_GET['q'] ?? '');
$page            = max(1, (int)($_GET['page'] ?? 1));

// Construir query
$where = [];
$params = [];

if ($status_filter && in_array($status_filter, ['draft', 'published'])) {
    $where[] = 'p.status = ?';
    $params[] = $status_filter;
}
if ($category_filter) {
    $where[] = 'p.category_id = ?';
    $params[] = $category_filter;
}
if ($search_filter) {
    $where[] = '(p.title LIKE ? OR p.content LIKE ?)';
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total
$stmt = db()->prepare("SELECT COUNT(*) FROM posts p $where_sql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$pagination = paginate($total, 20, $page);

// Artículos
$stmt = db()->prepare("SELECT p.*, c.name as category_name, u.name as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    $where_sql
    ORDER BY p.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$posts = $stmt->fetchAll();

$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
?>

<div class="page-header">
    <h1 class="page-title">Artículos</h1>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-ai" id="btnAiGenerate"><i class="fas fa-robot"></i> Nuevo con IA</button>
        <a href="post-editor.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo artículo</a>
    </div>
</div>

<!-- Modal: Generador IA -->
<div class="modal" id="aiModal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-robot"></i> Generador de artículos con IA</h3>
            <button class="modal-close" id="closeAiModal">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Step 1: Tema -->
            <div id="aiStep1">
                <p class="ai-step-label">Paso 1 de 3 &mdash; Introduce el tema</p>
                <div class="form-group">
                    <label>¿Sobre qué quieres escribir?</label>
                    <input type="text" id="aiTopic" placeholder="Ej: requisitos arraigo social 2026, renovar NIE, nacionalidad por residencia..." class="input-lg">
                    <small class="text-muted">Describe el tema principal del artículo. La IA generará 5 títulos optimizados para SEO.</small>
                </div>
                <div class="form-group">
                    <label>Categoría (opcional)</label>
                    <select id="aiCategory">
                        <option value="">Sin categoría</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary btn-block" id="aiSuggestBtn">
                    <i class="fas fa-magic"></i> Generar títulos SEO
                </button>
            </div>

            <!-- Step 2: Seleccionar título -->
            <div id="aiStep2" style="display:none;">
                <p class="ai-step-label">Paso 2 de 3 &mdash; Elige un título</p>
                <p class="text-muted" style="margin-bottom:15px;">Selecciona el título que mejor se adapte a tu artículo:</p>
                <div id="aiTitlesList"></div>
                <div style="margin-top:15px; display:flex; gap:10px;">
                    <button class="btn btn-outline-sm" id="aiBackBtn"><i class="fas fa-arrow-left"></i> Volver</button>
                    <button class="btn btn-primary" id="aiGenerateBtn" disabled>
                        <i class="fas fa-pen-fancy"></i> Generar artículo completo
                    </button>
                </div>
            </div>

            <!-- Step 3: Generando -->
            <div id="aiStep3" style="display:none;">
                <p class="ai-step-label">Paso 3 de 3 &mdash; Generando artículo</p>
                <div class="ai-loading">
                    <div class="ai-spinner"></div>
                    <p id="aiLoadingText">Generando artículo completo...</p>
                    <small class="text-muted">Esto puede tardar entre 30-60 segundos</small>
                </div>
            </div>

            <!-- Error state -->
            <div id="aiError" style="display:none;">
                <div class="alert alert-error" id="aiErrorText"></div>
                <button class="btn btn-outline-sm" id="aiRetryBtn"><i class="fas fa-redo"></i> Reintentar</button>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="filters-bar">
    <form method="GET" class="filters-form">
        <select name="status" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Publicados</option>
            <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Borradores</option>
        </select>
        <select name="category" onchange="this.form.submit()">
            <option value="">Todas las categorías</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="search-input">
            <input type="text" name="q" value="<?= e($search_filter) ?>" placeholder="Buscar artículos...">
            <button type="submit"><i class="fas fa-search"></i></button>
        </div>
    </form>
</div>

<!-- Tabla de artículos -->
<div class="card">
    <div class="card-body">
        <?php if (empty($posts)): ?>
            <p class="text-muted text-center" style="padding: 40px;">No se encontraron artículos.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:50px;">Img</th>
                        <th>Título</th>
                        <th>Estado</th>
                        <th>Categoría</th>
                        <th>Autor</th>
                        <th>Visitas</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <?php if ($post['featured_image']): ?>
                                <img src="<?= e($post['featured_image']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
                            <?php else: ?>
                                <span style="display:inline-block;width:40px;height:40px;background:#eee;border-radius:4px;text-align:center;line-height:40px;color:#bbb;font-size:14px;"><i class="fas fa-image"></i></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="post-editor.php?id=<?= $post['id'] ?>" class="post-title-link">
                                <?= e($post['title']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-<?= $post['status'] === 'published' ? 'success' : 'warning' ?>">
                                <?= $post['status'] === 'published' ? 'Publicado' : 'Borrador' ?>
                            </span>
                        </td>
                        <td><?= e($post['category_name'] ?? '—') ?></td>
                        <td><?= e($post['author_name']) ?></td>
                        <td><?= number_format($post['views']) ?></td>
                        <td><?= time_ago($post['created_at']) ?></td>
                        <td class="actions">
                            <a href="post-editor.php?id=<?= $post['id'] ?>" title="Editar"><i class="fas fa-edit"></i></a>
                            <?php if ($post['status'] === 'published'): ?>
                                <a href="<?= BLOG_URL . '/' . e($post['slug']) ?>" target="_blank" title="Ver"><i class="fas fa-external-link-alt"></i></a>
                            <?php endif; ?>
                            <button class="btn-delete" data-id="<?= $post['id'] ?>" data-title="<?= e($post['title']) ?>" title="Eliminar"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="admin-pagination">
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <a href="?page=<?= $i ?>&status=<?= e($status_filter) ?>&category=<?= $category_filter ?>&q=<?= e($search_filter) ?>"
                           class="<?= $i === $pagination['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$extra_scripts = '<script>const CSRF_TOKEN = "' . csrf_token() . '"; const ADMIN_URL = "' . ADMIN_URL . '";</script>';
require_once __DIR__ . '/../templates/admin-footer.php';
?>
