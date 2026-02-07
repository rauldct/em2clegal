<?php
$page_title = 'Editor de artículo';
$extra_head = '<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"></script>';

require_once __DIR__ . '/../templates/admin-header.php';
require_once __DIR__ . '/../includes/csrf.php';

$post_id = (int)($_GET['id'] ?? 0);
$post = null;
$post_tags = [];

// Cargar artículo existente
if ($post_id) {
    $stmt = db()->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    if (!$post) {
        flash_set('error', 'Artículo no encontrado.');
        redirect('post-list.php');
    }
    // Cargar tags del artículo
    $stmt = db()->prepare('SELECT t.id, t.name FROM tags t INNER JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?');
    $stmt->execute([$post_id]);
    $post_tags = $stmt->fetchAll();
}

// Guardar artículo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title       = trim($_POST['title'] ?? '');
    $slug        = trim($_POST['slug'] ?? '');
    $content     = $_POST['content'] ?? '';
    $excerpt     = trim($_POST['excerpt'] ?? '');
    $status      = $_POST['status'] ?? 'draft';
    $category_id = (int)($_POST['category_id'] ?? 0) ?: null;
    $meta_title  = trim($_POST['meta_title'] ?? '');
    $meta_desc   = trim($_POST['meta_description'] ?? '');
    $featured    = trim($_POST['featured_image'] ?? '');
    $published_at = trim($_POST['published_at'] ?? '');
    $tag_ids     = $_POST['tag_ids'] ?? [];

    // Validar
    if (!$title) {
        flash_set('error', 'El título es obligatorio.');
    } else {
        // Slug
        if (!$slug) $slug = slugify($title);
        $slug = unique_slug($slug, 'posts', $post_id ?: null);

        // Sanitizar contenido
        $content = sanitize_html($content);

        // Extracto automático
        if (!$excerpt && $content) {
            $excerpt = auto_excerpt($content);
        }

        // Fecha de publicación
        if ($status === 'published' && !$published_at) {
            $published_at = date('Y-m-d H:i:s');
        }

        $data = [
            'title'            => $title,
            'slug'             => $slug,
            'content'          => $content,
            'excerpt'          => $excerpt,
            'featured_image'   => $featured,
            'status'           => $status,
            'category_id'      => $category_id,
            'meta_title'       => $meta_title,
            'meta_description' => $meta_desc,
            'published_at'     => $published_at ?: null,
        ];

        if ($post_id) {
            // Actualizar
            $fields = [];
            $values = [];
            foreach ($data as $k => $v) {
                $fields[] = "`$k` = ?";
                $values[] = $v;
            }
            $values[] = $post_id;
            $stmt = db()->prepare('UPDATE posts SET ' . implode(', ', $fields) . ' WHERE id = ?');
            $stmt->execute($values);
        } else {
            // Crear
            $data['author_id'] = $current_user['id'];
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            $stmt = db()->prepare('INSERT INTO posts (`' . implode('`, `', $fields) . '`) VALUES (' . implode(', ', $placeholders) . ')');
            $stmt->execute(array_values($data));
            $post_id = (int)db()->lastInsertId();
        }

        // Actualizar tags
        db()->prepare('DELETE FROM post_tags WHERE post_id = ?')->execute([$post_id]);
        if (!empty($tag_ids)) {
            $stmt = db()->prepare('INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)');
            foreach ($tag_ids as $tid) {
                $stmt->execute([$post_id, (int)$tid]);
            }
        }

        // Notificar a Google/Bing si se publica
        if ($status === 'published') {
            $post_url = BLOG_URL . '/' . $slug;
            ping_search_engines($post_url);
        }

        flash_set('success', 'Artículo guardado correctamente.');
        redirect("post-editor.php?id=$post_id");
    }
}

// Cargar categorías y tags
$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$all_tags = db()->query('SELECT * FROM tags ORDER BY name')->fetchAll();
$selected_tag_ids = array_column($post_tags, 'id');
?>

<div class="editor-header">
    <h1 class="page-title"><?= $post_id ? 'Editar artículo' : 'Nuevo artículo' ?></h1>
    <div class="editor-actions">
        <a href="post-list.php" class="btn btn-outline-sm">&larr; Volver a la lista</a>
        <?php if ($post_id && $post['status'] === 'published'): ?>
            <a href="<?= BLOG_URL . '/' . e($post['slug']) ?>" target="_blank" class="btn btn-outline-sm"><i class="fas fa-external-link-alt"></i> Ver</a>
        <?php endif; ?>
    </div>
</div>

<form method="POST" id="postForm" class="editor-form">
    <?= csrf_field() ?>

    <div class="editor-layout">
        <!-- Columna principal (70%) -->
        <div class="editor-main">
            <div class="form-group">
                <label for="title">Título</label>
                <input type="text" id="title" name="title" value="<?= e($post['title'] ?? '') ?>" placeholder="Escribe el título del artículo..." required class="input-lg">
            </div>

            <div class="form-group">
                <label for="slug">URL (slug)</label>
                <div class="slug-preview">
                    <span class="slug-base"><?= BLOG_URL ?>/</span>
                    <input type="text" id="slug" name="slug" value="<?= e($post['slug'] ?? '') ?>" placeholder="se-genera-automaticamente">
                </div>
            </div>

            <div class="form-group">
                <label>Contenido</label>
                <textarea id="content" name="content" class="tinymce-editor"><?= e($post['content'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="excerpt">Extracto (resumen)</label>
                <textarea id="excerpt" name="excerpt" rows="3" placeholder="Resumen breve del artículo (se genera automáticamente si se deja vacío)"><?= e($post['excerpt'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Barra lateral (30%) -->
        <div class="editor-sidebar">
            <!-- Estado -->
            <div class="sidebar-card">
                <h4>Publicación</h4>
                <div class="form-group">
                    <label for="status">Estado</label>
                    <select id="status" name="status">
                        <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Borrador</option>
                        <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="published_at">Fecha de publicación</label>
                    <input type="datetime-local" id="published_at" name="published_at"
                        value="<?= $post && $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '' ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <p class="autosave-status" id="autosaveStatus"></p>
            </div>

            <!-- Categoría -->
            <div class="sidebar-card">
                <h4>Categoría</h4>
                <select name="category_id" id="category_id">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Etiquetas -->
            <div class="sidebar-card">
                <h4>Etiquetas</h4>
                <div class="tags-checkboxes">
                    <?php foreach ($all_tags as $tag): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="tag_ids[]" value="<?= $tag['id'] ?>"
                                <?= in_array($tag['id'], $selected_tag_ids) ? 'checked' : '' ?>>
                            <?= e($tag['name']) ?>
                        </label>
                    <?php endforeach; ?>
                    <?php if (empty($all_tags)): ?>
                        <p class="text-muted">No hay etiquetas. <a href="tags.php">Crear una</a>.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Imagen destacada -->
            <div class="sidebar-card">
                <h4>Imagen destacada</h4>
                <div id="featuredImagePreview">
                    <?php if (!empty($post['featured_image'])): ?>
                        <img src="<?= e($post['featured_image']) ?>" alt="Imagen destacada" style="max-width:100%; border-radius:6px;">
                    <?php endif; ?>
                </div>
                <input type="hidden" id="featured_image" name="featured_image" value="<?= e($post['featured_image'] ?? '') ?>">
                <button type="button" class="btn btn-outline-sm btn-block" id="selectFeaturedImage">
                    <i class="fas fa-image"></i> Seleccionar imagen
                </button>
                <button type="button" class="btn btn-outline-sm btn-block" id="removeFeaturedImage" style="<?= empty($post['featured_image']) ? 'display:none' : '' ?>">
                    <i class="fas fa-times"></i> Quitar imagen
                </button>
            </div>

            <!-- SEO -->
            <div class="sidebar-card">
                <h4>SEO</h4>
                <div class="form-group">
                    <label for="meta_title">Meta título <small class="char-count" id="metaTitleCount">0/70</small></label>
                    <input type="text" id="meta_title" name="meta_title" value="<?= e($post['meta_title'] ?? '') ?>" maxlength="70" placeholder="Título para Google">
                </div>
                <div class="form-group">
                    <label for="meta_description">Meta descripción <small class="char-count" id="metaDescCount">0/170</small></label>
                    <textarea id="meta_description" name="meta_description" rows="3" maxlength="170" placeholder="Descripción para Google"><?= e($post['meta_description'] ?? '') ?></textarea>
                </div>
                <div class="seo-preview">
                    <div class="seo-preview-title" id="seoPreviewTitle"><?= e($post['meta_title'] ?? $post['title'] ?? 'Título del artículo') ?></div>
                    <div class="seo-preview-url"><?= BLOG_URL ?>/<?= e($post['slug'] ?? 'url-del-articulo') ?></div>
                    <div class="seo-preview-desc" id="seoPreviewDesc"><?= e($post['meta_description'] ?? $post['excerpt'] ?? 'Descripción del artículo...') ?></div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal de medios -->
<div class="modal" id="mediaModal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Seleccionar imagen</h3>
            <button class="modal-close" id="closeMediaModal">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Subir imagen -->
            <div class="media-upload-area" id="mediaUploadArea">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Arrastra una imagen aquí o <label for="mediaFileInput" class="upload-link">selecciona un archivo</label></p>
                <input type="file" id="mediaFileInput" accept="image/*" style="display:none">
            </div>

            <!-- Sugerencias de Unsplash -->
            <div class="unsplash-section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <h4 style="margin:0; font-size:0.9rem; color:var(--primary);"><i class="fas fa-image"></i> Imágenes sugeridas</h4>
                    <button type="button" class="btn btn-outline-sm" id="refreshUnsplash"><i class="fas fa-sync-alt"></i> Otras imágenes</button>
                </div>
                <div class="unsplash-grid" id="unsplashGrid">
                    <p class="text-muted" style="text-align:center; padding:20px;">Haz clic en "Otras imágenes" para ver sugerencias.</p>
                </div>
                <div id="unsplashLoading" style="display:none; text-align:center; padding:20px;">
                    <div class="ai-spinner" style="width:30px; height:30px; border-width:3px; margin-bottom:10px;"></div>
                    <p class="text-muted" style="font-size:0.85rem;">Descargando imagen...</p>
                </div>
            </div>

            <div class="media-grid" id="mediaGrid"></div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '
<script>
// Configurar TinyMCE
tinymce.init({
    selector: ".tinymce-editor",
    height: 500,
    language: "es",
    plugins: "lists link image table code fullscreen media wordcount preview",
    toolbar: "undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist | link image media | table | code fullscreen",
    menubar: "file edit view insert format table",
    content_style: "body { font-family: Poppins, sans-serif; font-size: 16px; line-height: 1.7; color: #333; max-width: 800px; margin: 0 auto; }",
    image_advtab: true,
    link_default_target: "_blank",
    branding: false,
    promotion: false,
    setup: function(editor) {
        editor.on("change keyup", function() {
            editor.save();
        });
    }
});

// Variables globales para el editor
const POST_ID = ' . ($post_id ?: 0) . ';
const CSRF_TOKEN = "' . csrf_token() . '";
const ADMIN_URL = "' . ADMIN_URL . '";
const UPLOADS_URL = "' . UPLOADS_URL . '";
</script>';
require_once __DIR__ . '/../templates/admin-footer.php';
?>
