<?php

require_once __DIR__ . '/save_json.php';

function fg_save_knowledge_base(array $dataset, string $reason = 'Save knowledge base', array $context = []): void
{
    fg_save_json('knowledge_base', $dataset, $reason, $context);
}
