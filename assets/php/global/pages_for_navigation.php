<?php

require_once __DIR__ . '/load_pages.php';
require_once __DIR__ . '/filter_pages_for_user.php';

function fg_pages_for_navigation(?array $user): array
{
    $pages = fg_load_pages();
    $records = $pages['records'] ?? [];
    $accessible = fg_filter_pages_for_user($records, $user);
    $navigation = [];
    foreach ($accessible as $page) {
        if (empty($page['show_in_navigation'])) {
            continue;
        }
        $navigation[] = [
            'title' => $page['title'] ?? $page['slug'] ?? 'Page',
            'slug' => $page['slug'] ?? '',
            'summary' => $page['summary'] ?? '',
        ];
    }

    usort($navigation, static function (array $a, array $b): int {
        return strcasecmp($a['title'] ?? '', $b['title'] ?? '');
    });

    return $navigation;
}

