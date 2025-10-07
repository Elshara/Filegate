<?php

function fg_render_page_list(array $pages): string
{
    if (empty($pages)) {
        return '<section class="page-list"><p class="page-empty">No published pages are available yet.</p></section>';
    }

    $items = '';
    foreach ($pages as $page) {
        $items .= '<li class="page-list-item">';
        $items .= '<a class="page-list-link" href="/page.php?slug=' . urlencode((string) ($page['slug'] ?? '')) . '">';
        $items .= '<h2>' . htmlspecialchars($page['title'] ?? 'Page') . '</h2>';
        if (!empty($page['summary'])) {
            $items .= '<p>' . htmlspecialchars($page['summary']) . '</p>';
        }
        $items .= '</a>';
        $items .= '</li>';
    }

    return '<section class="page-list"><h1>Pages</h1><ul>' . $items . '</ul></section>';
}

