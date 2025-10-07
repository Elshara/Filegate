<?php

require_once __DIR__ . '/ensure_data_directory.php';
require_once __DIR__ . '/json_path.php';

function fg_save_json(string $name, array $data): void
{
    fg_ensure_data_directory();
    $path = fg_json_path($name);
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new RuntimeException('Unable to encode data for: ' . $name);
    }

    if (file_put_contents($path, $encoded) === false) {
        throw new RuntimeException('Unable to write data file: ' . $name);
    }
}

