<?php

require_once __DIR__ . '/dataset_path.php';
require_once __DIR__ . '/default_knowledge_categories_dataset.php';
require_once __DIR__ . '/load_json.php';

function fg_load_knowledge_categories(): array
{
    $path = fg_dataset_path('knowledge_categories', 'json');
    if (!is_file($path)) {
        return fg_default_knowledge_categories_dataset();
    }

    $data = fg_load_json($path);
    if (!is_array($data)) {
        return fg_default_knowledge_categories_dataset();
    }

    return $data;
}
