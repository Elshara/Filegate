<?php

require_once __DIR__ . '/load_knowledge_base.php';
require_once __DIR__ . '/save_knowledge_base.php';
require_once __DIR__ . '/default_knowledge_base_dataset.php';

function fg_delete_knowledge_article(int $articleId, array $context = []): bool
{
    if ($articleId <= 0) {
        throw new InvalidArgumentException('A valid article ID is required.');
    }

    try {
        $dataset = fg_load_knowledge_base();
    } catch (Throwable $exception) {
        $dataset = fg_default_knowledge_base_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_knowledge_base_dataset();
    }

    $deleted = false;
    $records = [];
    foreach ($dataset['records'] as $record) {
        if (!is_array($record)) {
            continue;
        }

        if ((int) ($record['id'] ?? 0) === $articleId) {
            $deleted = true;
            continue;
        }

        $records[] = $record;
    }

    if (!$deleted) {
        return false;
    }

    $dataset['records'] = $records;
    fg_save_knowledge_base($dataset, 'Delete knowledge article', $context + ['knowledge_article_id' => $articleId]);

    return true;
}
