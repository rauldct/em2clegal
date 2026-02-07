<?php
$page_title = 'Usuarios';
require_once __DIR__ . '/../templates/admin-header.php';
require_once __DIR__ . '/../includes/csrf.php';
auth_require_admin();

// Crear/Editar/Eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'save';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'delete' && $id && $id !== $current_user['id']) {
        // Reasignar posts al admin actual
        db()->prepare('UPDATE posts SET author_id = ? WHERE author_id = ?')->execute([$current_user['id'], $id]);
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        flash_set('success', 'Usuario eliminado.');
    } elseif ($action === 'save') {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role'] ?? 'editor';
        $pass  = $_POST['password'] ?? '';

        if (!$name || !$email) {
            flash_set('error', 'Nombre y email son obligatorios.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Email no válido.');
        } else {
            // Verificar email único
            $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                flash_set('error', 'Ya existe un usuario con ese email.');
            } else {
                if ($id) {
                    // Actualizar
                    $sql = 'UPDATE users SET name=?, email=?, role=?';
                    $params = [$name, $email, $role];
                    if ($pass) {
                        if (strlen($pass) < 8) {
                            flash_set('error', 'La contraseña debe tener al menos 8 caracteres.');
                            redirect('users.php');
                        }
                        $sql .= ', password_hash=?';
                        $params[] = password_hash($pass, PASSWORD_DEFAULT);
                    }
                    $sql .= ' WHERE id=?';
                    $params[] = $id;
                    db()->prepare($sql)->execute($params);
                    flash_set('success', 'Usuario actualizado.');
                } else {
                    // Crear
                    if (!$pass || strlen($pass) < 8) {
                        flash_set('error', 'La contraseña debe tener al menos 8 caracteres.');
                        redirect('users.php');
                    }
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)');
                    $stmt->execute([$name, $email, $hash, $role]);
                    flash_set('success', 'Usuario creado.');
                }
            }
        }
    }
    redirect('users.php');
}

$users = db()->query('SELECT * FROM users ORDER BY name')->fetchAll();
$edit_user = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit_user = $stmt->fetch();
}
?>

<h1 class="page-title">Usuarios</h1>

<div class="two-columns">
    <div class="card">
        <div class="card-header">
            <h3><?= $edit_user ? 'Editar usuario' : 'Nuevo usuario' ?></h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="name" value="<?= e($edit_user['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= e($edit_user['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Contraseña <?= $edit_user ? '(dejar vacío para no cambiar)' : '' ?></label>
                    <input type="password" name="password" minlength="8" <?= $edit_user ? '' : 'required' ?>>
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select name="role">
                        <option value="editor" <?= ($edit_user['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option>
                        <option value="admin" <?= ($edit_user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $edit_user ? 'Actualizar' : 'Crear' ?></button>
                <?php if ($edit_user): ?>
                    <a href="users.php" class="btn btn-outline-sm">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Usuarios</h3></div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'primary' : 'info' ?>"><?= e($u['role']) ?></span></td>
                        <td class="actions">
                            <a href="?edit=<?= $u['id'] ?>"><i class="fas fa-edit"></i></a>
                            <?php if ($u['id'] !== $current_user['id']): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este usuario?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-icon"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/admin-footer.php'; ?>
