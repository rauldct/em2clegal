<?php
/**
 * Paginación
 */

/**
 * Calcular datos de paginación
 */
function paginate(int $total, int $per_page, int $current_page): array
{
    $total_pages = max(1, (int)ceil($total / $per_page));
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;

    return [
        'total'        => $total,
        'per_page'     => $per_page,
        'current_page' => $current_page,
        'total_pages'  => $total_pages,
        'offset'       => $offset,
        'has_prev'     => $current_page > 1,
        'has_next'     => $current_page < $total_pages,
    ];
}

/**
 * Renderizar HTML de paginación
 */
function render_pagination(array $pagination, string $base_url): string
{
    if ($pagination['total_pages'] <= 1) return '';

    $html = '<nav class="pagination" aria-label="Paginación">';
    $html .= '<ul>';

    // Anterior
    if ($pagination['has_prev']) {
        $prev_url = $base_url . ($pagination['current_page'] - 1 === 1 ? '' : '/page/' . ($pagination['current_page'] - 1));
        $html .= '<li><a href="' . e($prev_url) . '" rel="prev">&laquo; Anterior</a></li>';
    }

    // Páginas
    $start = max(1, $pagination['current_page'] - 2);
    $end   = min($pagination['total_pages'], $pagination['current_page'] + 2);

    if ($start > 1) {
        $html .= '<li><a href="' . e($base_url) . '">1</a></li>';
        if ($start > 2) $html .= '<li class="dots"><span>...</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $url = $base_url . ($i === 1 ? '' : '/page/' . $i);
        $active = $i === $pagination['current_page'] ? ' class="active"' : '';
        $html .= '<li' . $active . '><a href="' . e($url) . '">' . $i . '</a></li>';
    }

    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) $html .= '<li class="dots"><span>...</span></li>';
        $url = $base_url . '/page/' . $pagination['total_pages'];
        $html .= '<li><a href="' . e($url) . '">' . $pagination['total_pages'] . '</a></li>';
    }

    // Siguiente
    if ($pagination['has_next']) {
        $next_url = $base_url . '/page/' . ($pagination['current_page'] + 1);
        $html .= '<li><a href="' . e($next_url) . '" rel="next">Siguiente &raquo;</a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}
