<?php

require_once __DIR__ . '/dataset_nature.php';
require_once __DIR__ . '/dataset_filename.php';
require_once __DIR__ . '/dataset_format.php';
require_once __DIR__ . '/data_directory.php';

function fg_dataset_path(string $name): string
{
    $format = fg_dataset_format($name);
    $nature = fg_dataset_nature($name);
    $directory = fg_data_directory($nature, $format);
    $filename = fg_dataset_filename($name);
    $extension = $format === 'xml' ? '.xml' : '.json';

    return $directory . '/' . $filename . $extension;
}

