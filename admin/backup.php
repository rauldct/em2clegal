<?php
$page_title = 'Backup';
require_once __DIR__ . '/../templates/admin-header.php';
require_once __DIR__ . '/../includes/csrf.php';
auth_require_admin();

$backup_dir = '/var/www/emc2/backup';

// Crear backup al hacer POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $type = $_POST['type'] ?? '';

    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }

    $date = date('Y-m-d_H-i-s');
    $success = false;
    $file = '';

    if ($type === 'database') {
        $file = "db_{$date}.sql.gz";
        $path = "$backup_dir/$file";
        $cmd = sprintf(
            'mysqldump -h %s -u %s -p%s %s --single-transaction --routines --triggers 2>/dev/null | gzip > %s',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME),
            escapeshellarg($path)
        );
        exec($cmd, $output, $ret);
        $success = ($ret === 0 && file_exists($path) && filesize($path) > 0);
        if (!$success && file_exists($path)) unlink($path);

    } elseif ($type === 'code') {
        $file = "code_{$date}.tar.gz";
        $path = "$backup_dir/$file";
        $cmd = sprintf(
            'tar -czf %s --exclude=%s --exclude=%s --exclude=%s -C %s %s 2>/dev/null',
            escapeshellarg($path),
            escapeshellarg(ROOT_PATH . '/assets/uploads'),
            escapeshellarg(ROOT_PATH . '/.git'),
            escapeshellarg(ROOT_PATH . '/node_modules'),
            escapeshellarg(dirname(ROOT_PATH)),
            escapeshellarg(basename(ROOT_PATH))
        );
        exec($cmd, $output, $ret);
        $success = ($ret === 0 && file_exists($path) && filesize($path) > 0);
        if (!$success && file_exists($path)) unlink($path);

    } elseif ($type === 'full') {
        $file = "full_{$date}.tar.gz";
        $path = "$backup_dir/$file";

        // First dump DB to temp file
        $tmp_sql = "$backup_dir/_tmp_db.sql";
        $cmd_db = sprintf(
            'mysqldump -h %s -u %s -p%s %s --single-transaction --routines --triggers > %s 2>/dev/null',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME),
            escapeshellarg($tmp_sql)
        );
        exec($cmd_db);

        // Then tar everything + the sql dump
        $cmd = sprintf(
            'tar -czf %s --exclude=%s --exclude=%s -C %s %s -C %s %s 2>/dev/null',
            escapeshellarg($path),
            escapeshellarg(ROOT_PATH . '/.git'),
            escapeshellarg(ROOT_PATH . '/node_modules'),
            escapeshellarg(dirname(ROOT_PATH)),
            escapeshellarg(basename(ROOT_PATH)),
            escapeshellarg($backup_dir),
            '_tmp_db.sql'
        );
        exec($cmd, $output, $ret);
        if (file_exists($tmp_sql)) unlink($tmp_sql);
        $success = ($ret === 0 && file_exists($path) && filesize($path) > 0);
        if (!$success && file_exists($path)) unlink($path);
    }

    if ($success) {
        flash_set('success', "Backup creado: $file (" . format_size(filesize("$backup_dir/$file")) . ")");
    } else {
        flash_set('error', 'Error al crear el backup.');
    }
    redirect('backup.php');
}

// Eliminar backup
if (isset($_GET['delete']) && isset($_GET['token'])) {
    if (hash_equals(csrf_token(), $_GET['token'])) {
        $del = basename($_GET['delete']);
        $del_path = "$backup_dir/$del";
        if (file_exists($del_path) && strpos(realpath($del_path), realpath($backup_dir)) === 0) {
            unlink($del_path);
            flash_set('success', "Backup eliminado: $del");
        }
    }
    redirect('backup.php');
}

// Listar backups existentes
$backups = [];
if (is_dir($backup_dir)) {
    $files = glob("$backup_dir/*.{gz,zip,sql}", GLOB_BRACE);
    foreach ($files as $f) {
        $name = basename($f);
        if ($name[0] === '_') continue; // skip temp files
        $backups[] = [
            'name' => $name,
            'size' => filesize($f),
            'date' => filemtime($f),
            'type' => get_backup_type($name),
        ];
    }
    // Sort by date desc
    usort($backups, function($a, $b) { return $b['date'] - $a['date']; });
}

function get_backup_type(string $name): string {
    if (str_starts_with($name, 'db_')) return 'Base de datos';
    if (str_starts_with($name, 'code_')) return 'Código';
    if (str_starts_with($name, 'full_')) return 'Completo';
    if (str_starts_with($name, 'uploads_')) return 'Uploads';
    return 'Otro';
}

function format_size(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

function type_icon(string $type): string {
    return match($type) {
        'Base de datos' => 'fa-database',
        'Código' => 'fa-code',
        'Completo' => 'fa-archive',
        'Uploads' => 'fa-images',
        default => 'fa-file',
    };
}

function type_badge(string $type): string {
    return match($type) {
        'Base de datos' => 'info',
        'Código' => 'warning',
        'Completo' => 'success',
        default => 'primary',
    };
}
?>

<h1 class="page-title">Backup</h1>

<!-- Crear Backup -->
<div class="backup-actions">
    <form method="POST" class="backup-card-form">
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="database">
        <button type="submit" class="backup-card">
            <div class="backup-card-icon" style="background:#d1ecf1; color:#0c5460;">
                <i class="fas fa-database"></i>
            </div>
            <div class="backup-card-info">
                <h4>Base de datos</h4>
                <p>Exportar todas las tablas, artículos, categorías, usuarios y configuración.</p>
            </div>
            <i class="fas fa-download backup-card-arrow"></i>
        </button>
    </form>

    <form method="POST" class="backup-card-form">
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="code">
        <button type="submit" class="backup-card">
            <div class="backup-card-icon" style="background:#fff3cd; color:#856404;">
                <i class="fas fa-code"></i>
            </div>
            <div class="backup-card-info">
                <h4>Código</h4>
                <p>Todos los archivos PHP, CSS, JS y plantillas (sin uploads).</p>
            </div>
            <i class="fas fa-download backup-card-arrow"></i>
        </button>
    </form>

    <form method="POST" class="backup-card-form">
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="full">
        <button type="submit" class="backup-card">
            <div class="backup-card-icon" style="background:#d4edda; color:#155724;">
                <i class="fas fa-archive"></i>
            </div>
            <div class="backup-card-info">
                <h4>Completo</h4>
                <p>Base de datos + código + uploads. Backup total del sitio.</p>
            </div>
            <i class="fas fa-download backup-card-arrow"></i>
        </button>
    </form>
</div>

<!-- Lista de Backups -->
<div class="card" style="margin-top:30px;">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> Backups guardados</h3>
        <span class="text-muted"><?= count($backups) ?> archivo<?= count($backups) !== 1 ? 's' : '' ?></span>
    </div>
    <div class="card-body">
        <?php if (empty($backups)): ?>
            <p class="text-muted text-center" style="padding:30px;">No hay backups todavía. Crea uno con los botones de arriba.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Tipo</th>
                        <th>Tamaño</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $bk): ?>
                    <tr>
                        <td>
                            <i class="fas <?= type_icon($bk['type']) ?>" style="color:#666; margin-right:8px;"></i>
                            <strong><?= e($bk['name']) ?></strong>
                        </td>
                        <td><span class="badge badge-<?= type_badge($bk['type']) ?>"><?= e($bk['type']) ?></span></td>
                        <td><?= format_size($bk['size']) ?></td>
                        <td><?= date('d/m/Y H:i', $bk['date']) ?></td>
                        <td class="actions">
                            <a href="<?= ADMIN_URL ?>/ajax/download-backup.php?file=<?= urlencode($bk['name']) ?>&token=<?= csrf_token() ?>" title="Descargar" class="btn btn-sm btn-primary" style="padding:5px 12px;">
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="?delete=<?= urlencode($bk['name']) ?>&token=<?= csrf_token() ?>" title="Eliminar" class="btn-delete" onclick="return confirm('¿Eliminar este backup?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/admin-footer.php'; ?>
