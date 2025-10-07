<?php

require_once __DIR__ . '/load_uploads.php';
require_once __DIR__ . '/save_uploads.php';

function fg_update_upload_meta(int $upload_id, array $meta): void
{
    $uploads = fg_load_uploads();
    $updated = false;

    foreach ($uploads['records'] as $index => $record) {
        if ((int) ($record['id'] ?? 0) === $upload_id) {
            $existing_meta = is_array($record['meta'] ?? null) ? $record['meta'] : [];
            $uploads['records'][$index]['meta'] = array_merge($existing_meta, $meta);
            $updated = true;
            break;
        }
    }

    if ($updated) {
        fg_save_uploads($uploads);
    }
}

