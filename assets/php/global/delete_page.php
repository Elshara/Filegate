<?php

require_once __DIR__ . '/load_pages.php';
require_once __DIR__ . '/save_pages.php';

function fg_delete_page(int $id): void
{
    $pages = fg_load_pages();
    $records = $pages['records'] ?? [];
    $filtered = [];
    foreach ($records as $record) {
        if ((int) ($record['id'] ?? 0) !== $id) {
            $filtered[] = $record;
        }
    }
    $pages['records'] = $filtered;
    fg_save_pages($pages);
}

