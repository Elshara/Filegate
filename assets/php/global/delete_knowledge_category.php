<?php

require_once __DIR__ . '/load_knowledge_categories.php';
require_once __DIR__ . '/save_knowledge_categories.php';
require_once __DIR__ . '/default_knowledge_categories_dataset.php';
require_once __DIR__ . '/load_knowledge_base.php';
require_once __DIR__ . '/save_knowledge_base.php';
require_once __DIR__ . '/default_knowledge_base_dataset.php';

function fg_delete_knowledge_category(int $categoryId, array $context = []): bool
{
    if ($categoryId <= 0) {
        throw new InvalidArgumentException('A valid knowledge category ID is required.');
    }

    try {
        $dataset = fg_load_knowledge_categories();
    } catch (Throwable $exception) {
        $dataset = fg_default_knowledge_categories_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_knowledge_categories_dataset();
    }

    $found = false;
    $filtered = [];
    foreach ($dataset['records'] as $record) {
        if (!is_array($record)) {
            continue;
        }
        if ((int) ($record['id'] ?? 0) === $categoryId) {
            $found = true;
            continue;
        }
        $filtered[] = $record;
    }

    if (!$found) {
        return false;
    }

    $dataset['records'] = $filtered;
    fg_save_knowledge_categories($dataset, 'Delete knowledge category', $context + ['knowledge_category_id' => $categoryId]);

    try {
        $knowledgeBase = fg_load_knowledge_base();
    } catch (Throwable $exception) {
        $knowledgeBase = fg_default_knowledge_base_dataset();
    }

    if (isset($knowledgeBase['records']) && is_array($knowledgeBase['records'])) {
        $updatedAny = false;
        foreach ($knowledgeBase['records'] as $index => $article) {
            if (!is_array($article)) {
                continue;
            }
            if ((int) ($article['category_id'] ?? 0) !== $categoryId) {
                continue;
            }
            $knowledgeBase['records'][$index]['category_id'] = null;
            $updatedAny = true;
        }
        if ($updatedAny) {
            fg_save_knowledge_base($knowledgeBase, 'Detach category from knowledge articles', $context + ['knowledge_category_id' => $categoryId]);
        }
    }

    return true;
}
