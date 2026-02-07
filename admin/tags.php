<?php
$page_title = 'Etiquetas';
require_once __DIR__ . '/../templates/admin-header.php';
require_once __DIR__ . '/../includes/csrf.php';

// Crear/Editar/Eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    if ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM post_tags WHERE tag_id = ?')->execute([$id]);
        db()->prepare('DELETE FROM tags WHERE id = ?')->execute([$id]);
        flash_set('success', 'Etiqueta eliminada.');
    } elseif ($name) {
        if (!$slug) $slug = slugify($name);
        $slug = unique_slug($slug, 'tags', $id ?: null);

        if ($id) {
            $stmt = db()->prepare('UPDATE tags SET name=?, slug=? WHERE id=?');
            $stmt->execute([$name, $slug, $id]);
            flash_set('success', 'Etiqueta actualizada.');
        } else {
            $stmt = db()->prepare('INSERT INTO tags (name, slug) VALUES (?,?)');
            $stmt->execute([$name, $slug]);
            flash_set('success', 'Etiqueta creada.');
        }
    }
    redirect('tags.php');
}

$tags = db()->query('SELECT t.*, (SELECT COUNT(*) FROM post_tags WHERE tag_id = t.id) as post_count FROM tags t ORDER BY name')->fetchAll();
$edit_tag = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM tags WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit_tag = $stmt->fetch();
}
?>

<h1 class="page-title">Etiquetas</h1>

<div class="two-columns">
    <div class="card">
        <div class="card-header">
            <h3><?= $edit_tag ? 'Editar etiqueta' : 'Nueva etiqueta' ?></h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <?php if ($edit_tag): ?>
                    <input type="hidden" name="id" value="<?= $edit_tag['id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="name" value="<?= e($edit_tag['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= e($edit_tag['slug'] ?? '') ?>" placeholder="auto-generado">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $edit_tag ? 'Actualizar' : 'Crear' ?></button>
                <?php if ($edit_tag): ?>
                    <a href="tags.php" class="btn btn-outline-sm">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Etiquetas existentes</h3></div>
        <div class="card-body">
            <?php if (empty($tags)): ?>
                <p class="text-muted">No hay etiquetas.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Nombre</th><th>Slug</th><th>Artículos</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($tags as $tag): ?>
                        <tr>
                            <td><?= e($tag['name']) ?></td>
                            <td><code><?= e($tag['slug']) ?></code></td>
                            <td><?= $tag['post_count'] ?></td>
                            <td class="actions">
                                <a href="?edit=<?= $tag['id'] ?>"><i class="fas fa-edit"></i></a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $tag['id'] ?>">
                                    <button type="submit" class="btn-icon"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/admin-footer.php'; ?>
