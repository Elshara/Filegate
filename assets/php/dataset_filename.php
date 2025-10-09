<?php

require_once __DIR__ . '/dataset_definition.php';

function fg_dataset_filename(string $name): string
{
    $definition = fg_dataset_definition($name);
    $file = $definition['file'] ?? $name;
    return is_string($file) && $file !== '' ? $file : $name;
}

