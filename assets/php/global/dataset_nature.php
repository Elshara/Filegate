<?php

require_once __DIR__ . '/dataset_definition.php';

function fg_dataset_nature(string $name): string
{
    $definition = fg_dataset_definition($name);
    $nature = $definition['nature'] ?? 'dynamic';
    return in_array($nature, ['static', 'dynamic'], true) ? $nature : 'dynamic';
}

