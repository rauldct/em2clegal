<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Si ya está logueado, redirigir
if (auth_check()) {
    redirect(ADMIN_URL . '/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = auth_login($email, $password);
    if ($result['success']) {
        redirect(ADMIN_URL . '/index.php');
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - EMC2 Legal CMS</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0a2540; --accent: #d4af37; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #f4f6f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { background: white; padding: 50px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); width: 400px; max-width: 95%; }
        .logo { font-size: 1.5rem; font-weight: 700; color: var(--primary); text-align: center; margin-bottom: 5px; }
        .logo span { display: block; font-size: 0.75rem; color: var(--accent); letter-spacing: 2px; text-transform: uppercase; }
        h2 { text-align: center; color: var(--primary); margin: 25px 0; font-size: 1.2rem; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 0.9rem; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; font-size: 0.95rem; }
        input:focus { outline: none; border-color: var(--accent); }
        .btn { width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; font-family: inherit; }
        .btn:hover { background: var(--accent); color: var(--primary); }
        .error { background: #fee; color: #c00; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9rem; text-align: center; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #999; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="logo">EMC2 LEGAL <span>Panel de Administración</span></div>
    <h2>Iniciar sesión</h2>

    <?php if ($error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Entrar</button>
    </form>

    <div class="back-link">
        <a href="/">&larr; Volver al sitio</a>
    </div>
</div>
</body>
</html>
