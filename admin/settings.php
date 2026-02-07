<?php
$page_title = 'Configuración';
require_once __DIR__ . '/../templates/admin-header.php';
require_once __DIR__ . '/../includes/csrf.php';
auth_require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $settings = [
        'blog_title'       => trim($_POST['blog_title'] ?? ''),
        'blog_description' => trim($_POST['blog_description'] ?? ''),
        'posts_per_page'   => max(1, (int)($_POST['posts_per_page'] ?? 9)),
        'analytics_code'   => trim($_POST['analytics_code'] ?? ''),
        'site_url'         => rtrim(trim($_POST['site_url'] ?? ''), '/'),
        'ai_provider'      => trim($_POST['ai_provider'] ?? 'anthropic'),
        'ai_api_key'       => trim($_POST['ai_api_key'] ?? ''),
        'ai_model'         => trim($_POST['ai_model'] ?? ''),
    ];
    foreach ($settings as $key => $value) {
        set_setting($key, (string)$value);
    }
    flash_set('success', 'Configuración guardada.');
    redirect('settings.php');
}
?>

<h1 class="page-title">Configuración del blog</h1>

<div class="card" style="max-width: 700px;">
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label>Título del blog</label>
                <input type="text" name="blog_title" value="<?= e(get_setting('blog_title', 'Blog EMC2 Legal')) ?>">
            </div>

            <div class="form-group">
                <label>Descripción del blog</label>
                <textarea name="blog_description" rows="2"><?= e(get_setting('blog_description', '')) ?></textarea>
            </div>

            <div class="form-group">
                <label>Artículos por página</label>
                <input type="number" name="posts_per_page" value="<?= e(get_setting('posts_per_page', '9')) ?>" min="1" max="50">
            </div>

            <div class="form-group">
                <label>URL del sitio (sin barra final)</label>
                <input type="url" name="site_url" value="<?= e(get_setting('site_url', SITE_URL)) ?>">
            </div>

            <div class="form-group">
                <label>Código Analytics (Google Analytics, etc.)</label>
                <textarea name="analytics_code" rows="4" placeholder="Pega aquí el código de seguimiento..."><?= e(get_setting('analytics_code', '')) ?></textarea>
                <small class="text-muted">Se insertará antes de &lt;/head&gt; en todas las páginas del blog.</small>
            </div>

            <hr style="margin: 30px 0; border: none; border-top: 2px solid #f0f0f0;">
            <h3 style="font-size: 1.1rem; margin-bottom: 20px; color: var(--primary);"><i class="fas fa-robot"></i> Generador de artículos con IA</h3>

            <div class="form-group">
                <label>Proveedor de IA</label>
                <select name="ai_provider" id="aiProvider">
                    <option value="anthropic" <?= get_setting('ai_provider', 'anthropic') === 'anthropic' ? 'selected' : '' ?>>Anthropic (Claude)</option>
                    <option value="openai" <?= get_setting('ai_provider', 'anthropic') === 'openai' ? 'selected' : '' ?>>OpenAI (GPT)</option>
                </select>
            </div>

            <div class="form-group">
                <label>API Key</label>
                <input type="password" name="ai_api_key" value="<?= e(get_setting('ai_api_key', '')) ?>" placeholder="sk-ant-... o sk-...">
                <small class="text-muted">Se necesita para el generador de artículos con IA. Obtener en <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a> o <a href="https://platform.openai.com/" target="_blank">platform.openai.com</a></small>
            </div>

            <div class="form-group">
                <label>Modelo (opcional)</label>
                <input type="text" name="ai_model" value="<?= e(get_setting('ai_model', '')) ?>" placeholder="Dejar vacío para usar el modelo por defecto">
                <small class="text-muted">Anthropic: claude-sonnet-4-5-20250929 (defecto) | OpenAI: gpt-4o (defecto)</small>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar configuración</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/admin-footer.php'; ?>
