<?php
$page_title = 'Medios';
require_once __DIR__ . '/../templates/admin-header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/image.php';

// Eliminar imagen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM media WHERE id = ?');
    $stmt->execute([$id]);
    $media_item = $stmt->fetch();
    if ($media_item) {
        delete_image($media_item['filepath']);
        db()->prepare('DELETE FROM media WHERE id = ?')->execute([$id]);
        flash_set('success', 'Imagen eliminada.');
    }
    redirect('media.php');
}

// Actualizar alt text
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_alt') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $alt = trim($_POST['alt_text'] ?? '');
    db()->prepare('UPDATE media SET alt_text = ? WHERE id = ?')->execute([$alt, $id]);
    flash_set('success', 'Texto alternativo actualizado.');
    redirect('media.php');
}

// Listar medios
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 24;
$total = (int)db()->query('SELECT COUNT(*) FROM media')->fetchColumn();
$offset = ($page - 1) * $per_page;
$media = db()->query("SELECT * FROM media ORDER BY created_at DESC LIMIT $per_page OFFSET $offset")->fetchAll();
$total_pages = (int)ceil($total / $per_page);
?>

<div class="page-header">
    <h1 class="page-title">Gestor de medios</h1>
</div>

<!-- Zona de upload -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-body">
        <div class="media-upload-zone" id="uploadZone">
            <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #ccc;"></i>
            <p>Arrastra imágenes aquí o <label for="fileInput" style="color: var(--accent); cursor: pointer; font-weight: 600;">selecciona archivos</label></p>
            <p class="text-muted" style="font-size: 0.8rem;">JPG, PNG, WebP, GIF — máximo 5MB</p>
            <input type="file" id="fileInput" accept="image/*" multiple style="display:none">
        </div>
        <div id="uploadProgress" style="display:none;">
            <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
            <p id="uploadStatus" class="text-muted" style="margin-top:5px;"></p>
        </div>
    </div>
</div>

<!-- Galería -->
<div class="media-gallery">
    <?php if (empty($media)): ?>
        <p class="text-muted text-center" style="padding: 40px;">No hay imágenes subidas.</p>
    <?php else: ?>
        <div class="media-grid-admin">
            <?php foreach ($media as $item): ?>
            <div class="media-item" data-id="<?= $item['id'] ?>">
                <div class="media-thumb">
                    <img src="<?= UPLOADS_URL . '/' . e($item['filepath']) ?>" alt="<?= e($item['alt_text']) ?>" loading="lazy">
                </div>
                <div class="media-info">
                    <input type="text" value="<?= UPLOADS_URL . '/' . e($item['filepath']) ?>" class="media-url" readonly onclick="this.select()">
                    <div class="media-actions">
                        <form method="POST" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_alt">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="text" name="alt_text" value="<?= e($item['alt_text']) ?>" placeholder="Texto alternativo..." class="alt-input">
                            <button type="submit" class="btn-icon" title="Guardar alt"><i class="fas fa-check"></i></button>
                        </form>
                        <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta imagen?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn-icon btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="admin-pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$extra_scripts = '<script>const CSRF_TOKEN = "' . csrf_token() . '"; const ADMIN_URL = "' . ADMIN_URL . '";</script>';
require_once __DIR__ . '/../templates/admin-footer.php';
?>
