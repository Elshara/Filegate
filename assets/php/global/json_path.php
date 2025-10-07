<?php

require_once __DIR__ . '/data_directory.php';
require_once __DIR__ . '/dataset_nature.php';
require_once __DIR__ . '/dataset_filename.php';

function fg_json_path(string $name): string
{
    $nature = fg_dataset_nature($name);
    $directory = fg_data_directory($nature);
    $filename = fg_dataset_filename($name);

    return $directory . '/' . $filename . '.json';
}

