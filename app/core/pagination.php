<?php
declare(strict_types=1);

function paginate(string $sql, array $params, int $page, int $perPage = 15): array
{
    $page    = max(1, $page);
    $offset  = ($page - 1) * $perPage;

    $countSql = 'SELECT COUNT(*) AS _total FROM (' . $sql . ') _pag_wrap';
    $countRow = db_one($countSql, $params);
    $total    = (int)($countRow['_total'] ?? 0);
    $pages    = $total > 0 ? (int)ceil($total / $perPage) : 1;

    $rows = db_all($sql . ' LIMIT ' . $perPage . ' OFFSET ' . $offset, $params);

    return [
        'rows'    => $rows,
        'total'   => $total,
        'page'    => $page,
        'pages'   => $pages,
        'perPage' => $perPage,
    ];
}

function render_pager(array $p, string $urlPattern): string
{
    if ($p['pages'] <= 1) {
        return '';
    }

    $current = $p['page'];
    $total   = $p['pages'];

    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center flex-wrap">';

    if ($current > 1) {
        $html .= '<li class="page-item">'
               . '<a class="page-link" href="' . htmlspecialchars(sprintf($urlPattern, $current - 1), ENT_QUOTES, 'UTF-8') . '">'
               . '&laquo; Prev</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&laquo; Prev</span></li>';
    }

    $start = max(1, $current - 3);
    $end   = min($total, $current + 3);

    if ($start > 1) {
        $html .= '<li class="page-item">'
               . '<a class="page-link" href="' . htmlspecialchars(sprintf($urlPattern, 1), ENT_QUOTES, 'UTF-8') . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $current) ? ' active' : '';
        $html  .= '<li class="page-item' . $active . '">'
                . '<a class="page-link" href="' . htmlspecialchars(sprintf($urlPattern, $i), ENT_QUOTES, 'UTF-8') . '">'
                . $i . '</a></li>';
    }

    if ($end < $total) {
        if ($end < $total - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        }
        $html .= '<li class="page-item">'
               . '<a class="page-link" href="' . htmlspecialchars(sprintf($urlPattern, $total), ENT_QUOTES, 'UTF-8') . '">'
               . $total . '</a></li>';
    }

    if ($current < $total) {
        $html .= '<li class="page-item">'
               . '<a class="page-link" href="' . htmlspecialchars(sprintf($urlPattern, $current + 1), ENT_QUOTES, 'UTF-8') . '">'
               . 'Next &raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}
