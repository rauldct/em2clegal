<?php
/**
 * Asistente de instalación del CMS - EMC2 Legal Blog
 * ELIMINAR ESTA CARPETA DESPUÉS DE LA INSTALACIÓN
 */

session_start();
$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

// Paso 2: Crear BD y usuario admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $db_host = trim($_POST['db_host'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    $admin_name  = trim($_POST['admin_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_pass  = $_POST['admin_pass'] ?? '';
    $site_url    = rtrim(trim($_POST['site_url'] ?? ''), '/');

    // Validaciones
    if (!$db_host || !$db_name || !$db_user) {
        $error = 'Completa todos los campos de la base de datos.';
    } elseif (!$admin_name || !$admin_email || !$admin_pass) {
        $error = 'Completa todos los campos del administrador.';
    } elseif (strlen($admin_pass) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email no válido.';
    } else {
        try {
            // Conectar a la BD
            $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Ejecutar schema
            $sql = file_get_contents(__DIR__ . '/schema.sql');
            $pdo->exec($sql);

            // Crear usuario admin
            $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$admin_name, $admin_email, $hash, 'admin']);

            // Actualizar config.php
            $config_path = dirname(__DIR__) . '/includes/config.php';
            $config = file_get_contents($config_path);
            $config = str_replace("'localhost'", "'" . addslashes($db_host) . "'", $config);
            $config = str_replace("'emc2legal_blog'", "'" . addslashes($db_name) . "'", $config);
            $config = str_replace("'root'", "'" . addslashes($db_user) . "'", $config);
            $config = preg_replace("/define\('DB_PASS',\s*''\)/", "define('DB_PASS', '" . addslashes($db_pass) . "')", $config);

            if ($site_url) {
                $config = str_replace('https://emc2legal.com', $site_url, $config);
                // Actualizar site_url en settings
                $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'site_url'");
                $stmt->execute([$site_url]);
            }

            file_put_contents($config_path, $config);

            $success = '¡Instalación completada!';
            $step = 3;
        } catch (PDOException $e) {
            $error = 'Error de conexión: ' . $e->getMessage();
        }
    }

    if ($error) $step = 2;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Blog EMC2 Legal</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0a2540; --accent: #d4af37; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #f4f6f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .install-box { background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); max-width: 600px; width: 100%; padding: 50px; }
        .logo { font-size: 1.5rem; font-weight: 700; color: var(--primary); text-align: center; margin-bottom: 5px; }
        .logo span { display: block; font-size: 0.75rem; color: var(--accent); letter-spacing: 2px; text-transform: uppercase; }
        .steps { display: flex; justify-content: center; gap: 10px; margin: 30px 0; }
        .step { width: 35px; height: 35px; border-radius: 50%; background: #e0e0e0; color: #999; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.85rem; }
        .step.active { background: var(--primary); color: white; }
        .step.done { background: var(--accent); color: var(--primary); }
        h2 { color: var(--primary); margin-bottom: 20px; font-size: 1.3rem; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 0.9rem; color: #333; }
        input[type="text"], input[type="email"], input[type="password"], input[type="url"] {
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; font-size: 0.95rem;
        }
        input:focus { outline: none; border-color: var(--accent); }
        .section-title { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); margin: 25px 0 15px; font-weight: 600; }
        .btn { display: block; width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; font-family: inherit; margin-top: 25px; }
        .btn:hover { background: var(--accent); color: var(--primary); }
        .error { background: #fee; color: #c00; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; }
        .success { background: #efe; color: #060; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; }
        .checklist { list-style: none; }
        .checklist li { padding: 8px 0; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 10px; }
        .check-ok { color: #0a0; font-weight: bold; }
        .check-fail { color: #c00; font-weight: bold; }
        .link { color: var(--primary); text-decoration: underline; }
    </style>
</head>
<body>
<div class="install-box">
    <div class="logo">EMC2 LEGAL <span>Blog CMS</span></div>

    <div class="steps">
        <div class="step <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">1</div>
        <div class="step <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">2</div>
        <div class="step <?= $step >= 3 ? 'active' : '' ?>">3</div>
    </div>

    <?php if ($step === 1): ?>
        <!-- Paso 1: Verificar requisitos -->
        <h2>Verificar requisitos del servidor</h2>
        <?php
        $checks = [
            ['PHP 7.4+', version_compare(PHP_VERSION, '7.4.0', '>=')],
            ['PDO MySQL', extension_loaded('pdo_mysql')],
            ['GD (imágenes)', extension_loaded('gd')],
            ['Fileinfo', extension_loaded('fileinfo')],
            ['includes/ escribible', is_writable(dirname(__DIR__) . '/includes')],
            ['assets/uploads/ escribible', is_writable(dirname(__DIR__) . '/assets/uploads')],
        ];
        $all_ok = true;
        foreach ($checks as $c) { if (!$c[1]) $all_ok = false; }
        ?>
        <ul class="checklist">
            <?php foreach ($checks as $check): ?>
                <li>
                    <span class="<?= $check[1] ? 'check-ok' : 'check-fail' ?>"><?= $check[1] ? '&#10004;' : '&#10008;' ?></span>
                    <?= htmlspecialchars($check[0]) ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($all_ok): ?>
            <a href="?step=2" class="btn" style="text-align:center; text-decoration:none; color:white; display:block;">Continuar</a>
        <?php else: ?>
            <p style="margin-top: 20px; color: #c00;">Corrige los requisitos marcados en rojo antes de continuar.</p>
        <?php endif; ?>

    <?php elseif ($step === 2): ?>
        <!-- Paso 2: Configuración -->
        <h2>Configurar base de datos y administrador</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="?step=2">
            <div class="section-title">Base de datos MySQL</div>
            <div class="form-group">
                <label>Servidor</label>
                <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
            </div>
            <div class="form-group">
                <label>Nombre de la BD</label>
                <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? 'emc2legal_blog') ?>" required>
            </div>
            <div class="form-group">
                <label>Usuario BD</label>
                <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Contraseña BD</label>
                <input type="password" name="db_pass" value="">
            </div>

            <div class="section-title">Cuenta de administrador</div>
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Contraseña (min. 8 caracteres)</label>
                <input type="password" name="admin_pass" minlength="8" required>
            </div>

            <div class="section-title">URL del sitio</div>
            <div class="form-group">
                <label>URL (sin barra final)</label>
                <input type="url" name="site_url" value="<?= htmlspecialchars($_POST['site_url'] ?? 'https://emc2legal.com') ?>">
            </div>

            <button type="submit" class="btn">Instalar</button>
        </form>

    <?php elseif ($step === 3): ?>
        <!-- Paso 3: Completado -->
        <div class="success">&#10004; ¡Instalación completada correctamente!</div>
        <h2>Siguiente pasos</h2>
        <ul class="checklist">
            <li><span class="check-ok">1.</span> <a href="/admin/login.php" class="link">Accede al panel de administración</a></li>
            <li><span class="check-ok">2.</span> Crea tu primer artículo en el blog</li>
            <li><span class="check-ok">3.</span> <strong>Elimina la carpeta /install/</strong> por seguridad</li>
        </ul>
        <a href="/admin/login.php" class="btn" style="text-align:center; text-decoration:none; color:white; display:block;">Ir al panel de administración</a>
    <?php endif; ?>
</div>
</body>
</html>
