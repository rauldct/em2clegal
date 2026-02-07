<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/../templates/admin-header.php';

// Estadísticas generales
$stats = [];
try {
    $stats['total_posts']     = db()->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $stats['published_posts'] = db()->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
    $stats['draft_posts']     = db()->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn();
    $stats['categories']      = db()->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $stats['total_views']     = db()->query("SELECT COALESCE(SUM(views),0) FROM posts")->fetchColumn();
    $stats['media']           = db()->query("SELECT COUNT(*) FROM media")->fetchColumn();
} catch (Exception $e) {
    $stats = ['total_posts'=>0,'published_posts'=>0,'draft_posts'=>0,'categories'=>0,'total_views'=>0,'media'=>0];
}

// Visitas por artículo (top 10)
$views_by_post = db()->query("
    SELECT title, slug, views
    FROM posts
    WHERE status = 'published'
    ORDER BY views DESC
    LIMIT 10
")->fetchAll();

// Visitas por categoría
$views_by_category = db()->query("
    SELECT c.name, COALESCE(SUM(p.views), 0) as total_views, COUNT(p.id) as post_count
    FROM categories c
    LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published'
    GROUP BY c.id, c.name
    HAVING total_views > 0
    ORDER BY total_views DESC
")->fetchAll();

// Publicaciones por mes (últimos 6 meses)
$posts_by_month = db()->query("
    SELECT DATE_FORMAT(published_at, '%Y-%m') as month,
           COUNT(*) as count,
           SUM(views) as views
    FROM posts
    WHERE status = 'published' AND published_at IS NOT NULL
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
")->fetchAll();
$posts_by_month = array_reverse($posts_by_month);

// Últimos artículos
$recent_posts = db()->query("SELECT p.*, c.name as category_name, u.name as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

// Preparar datos JSON para las gráficas
$chart_posts_labels = array_map(function($p) { return mb_substr($p['title'], 0, 30) . (mb_strlen($p['title']) > 30 ? '...' : ''); }, $views_by_post);
$chart_posts_values = array_column($views_by_post, 'views');

$chart_cat_labels = array_column($views_by_category, 'name');
$chart_cat_values = array_column($views_by_category, 'total_views');

$months_es = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
$chart_month_labels = array_map(function($m) use ($months_es) {
    $parts = explode('-', $m['month']);
    return ($months_es[$parts[1]] ?? $parts[1]) . ' ' . $parts[0];
}, $posts_by_month);
$chart_month_views = array_column($posts_by_month, 'views');
$chart_month_posts = array_column($posts_by_month, 'count');
?>

<h1 class="page-title">Dashboard</h1>

<!-- Tarjetas de estadísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
        <div class="stat-info">
            <span class="stat-number"><?= $stats['total_posts'] ?></span>
            <span class="stat-label">Artículos totales</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #d4edda; color: #155724;"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <span class="stat-number"><?= $stats['published_posts'] ?></span>
            <span class="stat-label">Publicados</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff3cd; color: #856404;"><i class="fas fa-pencil-alt"></i></div>
        <div class="stat-info">
            <span class="stat-number"><?= $stats['draft_posts'] ?></span>
            <span class="stat-label">Borradores</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #d1ecf1; color: #0c5460;"><i class="fas fa-eye"></i></div>
        <div class="stat-info">
            <span class="stat-number"><?= number_format($stats['total_views']) ?></span>
            <span class="stat-label">Visitas totales</span>
        </div>
    </div>
</div>

<!-- Gráficas -->
<div class="dashboard-grid">
    <!-- Visitas por artículo -->
    <div class="card" style="grid-column: 1 / -1;">
        <div class="card-header"><h3><i class="fas fa-chart-bar"></i> Visitas por artículo</h3></div>
        <div class="card-body">
            <div style="position:relative; height:300px;">
                <canvas id="chartPostViews"></canvas>
            </div>
        </div>
    </div>

    <!-- Visitas por categoría -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-pie"></i> Visitas por categoría</h3></div>
        <div class="card-body">
            <div style="position:relative; height:280px;">
                <canvas id="chartCategoryViews"></canvas>
            </div>
        </div>
    </div>

    <!-- Actividad mensual -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-line"></i> Actividad mensual</h3></div>
        <div class="card-body">
            <div style="position:relative; height:280px;">
                <canvas id="chartMonthly"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tabla últimos artículos + Accesos rápidos -->
<div class="dashboard-grid" style="margin-top:20px;">
    <div class="card">
        <div class="card-header">
            <h3>Últimos artículos</h3>
            <a href="post-editor.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Nuevo</a>
        </div>
        <div class="card-body">
            <?php if (empty($recent_posts)): ?>
                <p class="text-muted">No hay artículos aún. <a href="post-editor.php">Crea el primero</a>.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Estado</th>
                            <th>Visitas</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_posts as $post): ?>
                        <tr>
                            <td><a href="post-editor.php?id=<?= $post['id'] ?>"><?= e($post['title']) ?></a></td>
                            <td>
                                <span class="badge badge-<?= $post['status'] === 'published' ? 'success' : 'warning' ?>">
                                    <?= $post['status'] === 'published' ? 'Publicado' : 'Borrador' ?>
                                </span>
                            </td>
                            <td><?= number_format($post['views']) ?></td>
                            <td><?= time_ago($post['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Accesos rápidos</h3>
        </div>
        <div class="card-body">
            <div class="quick-links">
                <a href="post-editor.php" class="quick-link"><i class="fas fa-plus-circle"></i> Nuevo artículo</a>
                <a href="categories.php" class="quick-link"><i class="fas fa-folder-plus"></i> Gestionar categorías</a>
                <a href="media.php" class="quick-link"><i class="fas fa-cloud-upload-alt"></i> Subir imágenes</a>
                <a href="<?= BLOG_URL ?>" target="_blank" class="quick-link"><i class="fas fa-external-link-alt"></i> Ver el blog</a>
                <a href="<?= BLOG_URL ?>/sitemap.xml" target="_blank" class="quick-link"><i class="fas fa-sitemap"></i> Ver sitemap</a>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
var colors = ["#0a2540","#d4af37","#2ecc71","#3498db","#e74c3c","#9b59b6","#f39c12","#1abc9c","#e67e22","#34495e"];

// 1. Visitas por artículo (horizontal bar)
new Chart(document.getElementById("chartPostViews"), {
    type: "bar",
    data: {
        labels: ' . json_encode($chart_posts_labels, JSON_UNESCAPED_UNICODE) . ',
        datasets: [{
            label: "Visitas",
            data: ' . json_encode($chart_posts_values) . ',
            backgroundColor: colors.slice(0, ' . count($chart_posts_values) . '),
            borderRadius: 4
        }]
    },
    options: {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(ctx) { return ctx.raw.toLocaleString() + " visitas"; }
                }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { callback: function(v) { return v.toLocaleString(); } } },
            y: { grid: { display: false } }
        }
    }
});

// 2. Visitas por categoría (donut)
new Chart(document.getElementById("chartCategoryViews"), {
    type: "doughnut",
    data: {
        labels: ' . json_encode($chart_cat_labels, JSON_UNESCAPED_UNICODE) . ',
        datasets: [{
            data: ' . json_encode($chart_cat_values) . ',
            backgroundColor: colors.slice(0, ' . count($chart_cat_values) . '),
            borderWidth: 2,
            borderColor: "#fff"
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: "right", labels: { padding: 12, usePointStyle: true, font: { size: 11 } } },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        var total = ctx.dataset.data.reduce(function(a,b){return a+b;},0);
                        var pct = Math.round(ctx.raw / total * 100);
                        return ctx.label + ": " + ctx.raw.toLocaleString() + " (" + pct + "%)";
                    }
                }
            }
        }
    }
});

// 3. Actividad mensual (line + bar)
new Chart(document.getElementById("chartMonthly"), {
    type: "bar",
    data: {
        labels: ' . json_encode($chart_month_labels, JSON_UNESCAPED_UNICODE) . ',
        datasets: [
            {
                type: "line",
                label: "Visitas",
                data: ' . json_encode($chart_month_views) . ',
                borderColor: "#d4af37",
                backgroundColor: "rgba(212,175,55,0.1)",
                fill: true,
                tension: 0.3,
                yAxisID: "y",
                pointRadius: 5,
                pointBackgroundColor: "#d4af37"
            },
            {
                type: "bar",
                label: "Artículos publicados",
                data: ' . json_encode($chart_month_posts) . ',
                backgroundColor: "rgba(10,37,64,0.7)",
                borderRadius: 4,
                yAxisID: "y1"
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: "top", labels: { usePointStyle: true, font: { size: 11 } } }
        },
        scales: {
            y:  { position: "left",  grid: { display: true }, title: { display: true, text: "Visitas", font: { size: 11 } } },
            y1: { position: "right", grid: { display: false }, title: { display: true, text: "Artículos", font: { size: 11 } }, min: 0,
                  ticks: { stepSize: 1 } },
            x:  { grid: { display: false } }
        }
    }
});
</script>';

require_once __DIR__ . '/../templates/admin-footer.php';
?>
