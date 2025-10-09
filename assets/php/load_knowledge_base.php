<?php

require_once __DIR__ . '/load_json.php';
require_once __DIR__ . '/default_knowledge_base_dataset.php';

function fg_load_knowledge_base(): array
{
    try {
        $dataset = fg_load_json('knowledge_base');
    } catch (Throwable $exception) {
        $dataset = fg_default_knowledge_base_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_knowledge_base_dataset();
    }

    return $dataset;
}
