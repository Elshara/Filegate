<?php

require_once __DIR__ . '/load_content_modules.php';
require_once __DIR__ . '/normalize_content_module.php';

function fg_list_content_modules(?string $dataset = null): array
{
    $modules = fg_load_content_modules();
    $records = $modules['records'] ?? [];
    if (!is_array($records)) {
        return [];
    }

    $datasetFilter = $dataset !== null ? strtolower(trim($dataset)) : null;
    $result = [];
    foreach ($records as $module) {
        if (!is_array($module)) {
            continue;
        }
        $normalized = fg_normalize_content_module_definition($module);
        if ($datasetFilter !== null && strtolower($normalized['dataset']) !== $datasetFilter) {
            continue;
        }
        $result[$normalized['key']] = $normalized;
    }

    ksort($result);

    return $result;
}
