<?php

require_once __DIR__ . '/load_content_modules.php';
require_once __DIR__ . '/save_content_modules.php';

function fg_delete_content_module(int $moduleId): bool
{
    if ($moduleId <= 0) {
        return false;
    }

    $modules = fg_load_content_modules();
    if (!isset($modules['records']) || !is_array($modules['records'])) {
        return false;
    }

    $records = $modules['records'];
    $updated = false;
    foreach ($records as $index => $record) {
        if ((int) ($record['id'] ?? 0) === $moduleId) {
            unset($records[$index]);
            $updated = true;
        }
    }

    if (!$updated) {
        return false;
    }

    $modules['records'] = array_values($records);
    fg_save_content_modules($modules);

    return true;
}
