<?php

require_once __DIR__ . '/dataset_manifest_path.php';
require_once __DIR__ . '/ensure_data_directory.php';

function fg_load_dataset_manifest(): array
{
    fg_ensure_data_directory();
    $path = fg_dataset_manifest_path();
    if (!file_exists($path)) {
        return [];
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Unable to read dataset manifest.');
    }

    $decoded = json_decode($contents, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

