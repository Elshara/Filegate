<?php

require_once __DIR__ . '/can_view_page.php';

function fg_filter_pages_for_user(array $pages, ?array $user): array
{
    $filtered = [];
    foreach ($pages as $page) {
        if (!is_array($page)) {
            continue;
        }
        if (fg_can_view_page($page, $user)) {
            $filtered[] = $page;
        }
    }

    return $filtered;
}

