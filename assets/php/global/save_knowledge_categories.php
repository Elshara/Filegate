<?php

require_once __DIR__ . '/dataset_path.php';
require_once __DIR__ . '/record_activity_event.php';
require_once __DIR__ . '/record_dataset_snapshot.php';
require_once __DIR__ . '/save_json.php';

function fg_save_knowledge_categories(array $dataset, string $message, array $context = []): void
{
    $path = fg_dataset_path('knowledge_categories', 'json');
    fg_record_dataset_snapshot('knowledge_categories', $path, $dataset, $context);
    fg_save_json($path, $dataset);
    fg_record_activity_event([
        'dataset' => 'knowledge_categories',
        'action' => 'save',
        'message' => $message,
        'context' => $context,
    ]);
}
