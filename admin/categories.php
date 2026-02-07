<?php
$page_title = 'Categorías';
require_once __DIR__ . '/../templates/admin-header.php';
require_once __DIR__ . '/../includes/csrf.php';

// Crear/Editar categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');
    $slug   = trim($_POST['slug'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $meta_t = trim($_POST['meta_title'] ?? '');
    $meta_d = trim($_POST['meta_description'] ?? '');

    if ($action === 'delete' && $id) {
        db()->prepare('UPDATE posts SET category_id = NULL WHERE category_id = ?')->execute([$id]);
        db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        flash_set('success', 'Categoría eliminada.');
    } elseif ($name) {
        if (!$slug) $slug = slugify($name);
        $slug = unique_slug($slug, 'categories', $id ?: null);

        if ($id) {
            $stmt = db()->prepare('UPDATE categories SET name=?, slug=?, description=?, meta_title=?, meta_description=? WHERE id=?');
            $stmt->execute([$name, $slug, $desc, $meta_t, $meta_d, $id]);
            flash_set('success', 'Categoría actualizada.');
        } else {
            $stmt = db()->prepare('INSERT INTO categories (name, slug, description, meta_title, meta_description) VALUES (?,?,?,?,?)');
            $stmt->execute([$name, $slug, $desc, $meta_t, $meta_d]);
            flash_set('success', 'Categoría creada.');
        }
    }
    redirect('categories.php');
}

// Listar
$categories = db()->query('SELECT c.*, (SELECT COUNT(*) FROM posts WHERE category_id = c.id) as post_count FROM categories c ORDER BY name')->fetchAll();
$edit_cat = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit_cat = $stmt->fetch();
}
?>

<h1 class="page-title">Categorías</h1>

<div class="two-columns">
    <!-- Formulario -->
    <div class="card">
        <div class="card-header">
            <h3><?= $edit_cat ? 'Editar categoría' : 'Nueva categoría' ?></h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <?php if ($edit_cat): ?>
                    <input type="hidden" name="id" value="<?= $edit_cat['id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="name" value="<?= e($edit_cat['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= e($edit_cat['slug'] ?? '') ?>" placeholder="auto-generado">
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" rows="3"><?= e($edit_cat['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Meta título (SEO)</label>
                    <input type="text" name="meta_title" value="<?= e($edit_cat['meta_title'] ?? '') ?>" maxlength="70">
                </div>
                <div class="form-group">
                    <label>Meta descripción (SEO)</label>
                    <textarea name="meta_description" rows="2" maxlength="170"><?= e($edit_cat['meta_description'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $edit_cat ? 'Actualizar' : 'Crear' ?>
                </button>
                <?php if ($edit_cat): ?>
                    <a href="categories.php" class="btn btn-outline-sm">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Lista -->
    <div class="card">
        <div class="card-header">
            <h3>Categorías existentes</h3>
        </div>
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <p class="text-muted">No hay categorías.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr><th>Nombre</th><th>Slug</th><th>Artículos</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= e($cat['name']) ?></td>
                            <td><code><?= e($cat['slug']) ?></code></td>
                            <td><?= $cat['post_count'] ?></td>
                            <td class="actions">
                                <a href="?edit=<?= $cat['id'] ?>" title="Editar"><i class="fas fa-edit"></i></a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta categoría?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                    <button type="submit" class="btn-icon" title="Eliminar"><i class="fas fa-trash"></i></button>
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
