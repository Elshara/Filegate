<?php

require_once __DIR__ . '/save_json.php';

function fg_save_knowledge_categories(array $dataset, ?string $reason = null, array $context = []): void
{
    fg_save_json('knowledge_categories', $dataset, $reason ?? 'Save knowledge categories dataset', $context);
}
