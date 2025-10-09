<?php

require_once __DIR__ . '/load_dataset_manifest.php';

function fg_list_datasets(): array
{
    $manifest = fg_load_dataset_manifest();
    if ($manifest === []) {
        return [];
    }

    return $manifest;
}

