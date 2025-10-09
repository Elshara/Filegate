<?php

require_once __DIR__ . '/default_knowledge_categories_dataset.php';
require_once __DIR__ . '/load_json.php';

function fg_load_knowledge_categories(): array
{
    $data = fg_load_json('knowledge_categories');
    if (!is_array($data) || empty($data)) {
        return fg_default_knowledge_categories_dataset();
    }

    return $data;
}
