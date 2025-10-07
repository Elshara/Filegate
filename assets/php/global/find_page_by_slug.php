<?php

require_once __DIR__ . '/load_pages.php';

function fg_find_page_by_slug(string $slug, ?array $source = null): ?array
{
    if ($source === null) {
        $pages = fg_load_pages();
        $records = $pages['records'] ?? [];
    } elseif (isset($source['records'])) {
        $records = $source['records'];
    } else {
        $records = $source;
    }

    foreach ($records as $page) {
        if (isset($page['slug']) && (string) $page['slug'] === $slug) {
            return $page;
        }
    }

    return null;
}

