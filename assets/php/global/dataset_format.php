<?php

require_once __DIR__ . '/dataset_definition.php';

function fg_dataset_format(string $name): string
{
    $definition = fg_dataset_definition($name);
    $format = strtolower((string) ($definition['format'] ?? 'json'));

    return in_array($format, ['json', 'xml'], true) ? $format : 'json';
}

