<?php
/**
 * Dibuja los links "Anterior / 1 2 3 / Siguiente"
 *
 * @param int    $page       Página actual
 * @param int    $totalPages Cantidad total de páginas
 * @param string $baseUrl    URL base (ej: "index.php")
 * @param array  $params     Otros parámetros GET a conservar (ej: ['search' => 'iphone'])
 */
function renderPagination($page, $totalPages, $baseUrl, $params = []) {
    if ($totalPages <= 1) return; // no hace falta paginación

    // Construye un query string conservando los filtros actuales
    $buildUrl = function($p) use ($baseUrl, $params) {
        $params['page'] = $p;
        return $baseUrl . '?' . http_build_query($params);
    };

    echo '<div class="pagination">';

    // Botón "Anterior"
    if ($page > 1) {
        echo '<a href="' . $buildUrl($page - 1) . '" class="btn btn-outline btn-sm">← Anterior</a>';
    }

    // Números de página
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $page ? 'btn-primary' : 'btn-outline';
        echo '<a href="' . $buildUrl($i) . '" class="btn ' . $active . ' btn-sm">' . $i . '</a>';
    }

    // Botón "Siguiente"
    if ($page < $totalPages) {
        echo '<a href="' . $buildUrl($page + 1) . '" class="btn btn-outline btn-sm">Siguiente →</a>';
    }

    echo '</div>';
}
?>
