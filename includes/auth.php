<?php
/**
 * Autenticación, sesiones y roles
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

/**
 * Intentar login con email y contraseña
 */
function auth_login(string $email, string $password): array
{
    // Rate limiting
    if (auth_is_locked_out($email)) {
        return ['success' => false, 'error' => 'Demasiados intentos. Espera 15 minutos.'];
    }

    $stmt = db()->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        auth_record_failed_attempt($email);
        return ['success' => false, 'error' => 'Email o contraseña incorrectos.'];
    }

    // Login exitoso
    auth_clear_attempts($email);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    session_regenerate_id(true);

    return ['success' => true, 'user' => $user];
}

/**
 * Cerrar sesión
 */
function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * ¿Está logueado?
 */
function auth_check(): bool
{
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

/**
 * Requerir login (redirige si no está autenticado)
 */
function auth_require(): void
{
    if (!auth_check()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

/**
 * Requerir rol admin
 */
function auth_require_admin(): void
{
    auth_require();
    if ($_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado.');
    }
}

/**
 * Obtener usuario actual
 */
function auth_user(): ?array
{
    if (!auth_check()) return null;
    return [
        'id'   => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role'],
    ];
}

// --- Rate Limiting ---

function auth_is_locked_out(string $email): bool
{
    $key = 'login_attempts_' . md5($email);
    if (!isset($_SESSION[$key])) return false;

    $data = $_SESSION[$key];
    if ($data['count'] >= LOGIN_MAX_ATTEMPTS) {
        if (time() - $data['last_attempt'] < LOGIN_LOCKOUT_MINUTES * 60) {
            return true;
        }
        // Lockout expirado
        unset($_SESSION[$key]);
    }
    return false;
}

function auth_record_failed_attempt(string $email): void
{
    $key = 'login_attempts_' . md5($email);
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
    }
    $_SESSION[$key]['count']++;
    $_SESSION[$key]['last_attempt'] = time();
}

function auth_clear_attempts(string $email): void
{
    $key = 'login_attempts_' . md5($email);
    unset($_SESSION[$key]);
}
