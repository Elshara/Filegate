<?php

require_once __DIR__ . '/load_dataset_manifest.php';

function fg_dataset_definition(string $name): array
{
    $manifest = fg_load_dataset_manifest();
    $definition = $manifest[$name] ?? [];
    if (!is_array($definition)) {
        return [];
    }

    return $definition;
}

