<?php

require_once __DIR__ . '/ensure_data_directory.php';
require_once __DIR__ . '/dataset_path.php';

function fg_load_dataset_contents(string $name): string
{
    fg_ensure_data_directory();
    $path = fg_dataset_path($name);
    if (!file_exists($path)) {
        return '';
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Unable to read dataset file: ' . $name);
    }

    return $contents;
}

