<?php

require_once __DIR__ . '/ensure_data_directory.php';
require_once __DIR__ . '/json_path.php';

function fg_load_json(string $name): array
{
    fg_ensure_data_directory();
    $path = fg_json_path($name);
    if (!file_exists($path)) {
        return [];
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Unable to read data file: ' . $name);
    }

    $decoded = json_decode($contents, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

