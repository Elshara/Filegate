<?php

require_once __DIR__ . '/load_knowledge_categories.php';
require_once __DIR__ . '/save_knowledge_categories.php';
require_once __DIR__ . '/default_knowledge_categories_dataset.php';
require_once __DIR__ . '/normalize_knowledge_category_slug.php';

function fg_update_knowledge_category(int $categoryId, array $input, array $context = []): ?array
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

    $updated = null;
    foreach ($dataset['records'] as $index => $record) {
        if (!is_array($record) || (int) ($record['id'] ?? 0) !== $categoryId) {
            continue;
        }

        $name = trim((string) ($input['name'] ?? $record['name'] ?? 'Untitled'));
        if ($name === '') {
            $name = 'Untitled';
        }

        $slugSource = trim((string) ($input['slug'] ?? $record['slug'] ?? $name));
        $slug = fg_normalize_knowledge_category_slug($slugSource);

        $description = trim((string) ($input['description'] ?? $record['description'] ?? ''));
        $visibility = strtolower(trim((string) ($input['visibility'] ?? $record['visibility'] ?? 'public')));
        if (!in_array($visibility, ['public', 'members', 'private'], true)) {
            $visibility = $record['visibility'] ?? 'public';
        }

        $ordering = (int) ($input['ordering'] ?? $record['ordering'] ?? 0);
        if ($ordering < 0) {
            $ordering = 0;
        }

        $dataset['records'][$index] = [
            'id' => $categoryId,
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'visibility' => $visibility,
            'ordering' => $ordering,
        ];

        $updated = $dataset['records'][$index];
        break;
    }

    if ($updated === null) {
        return null;
    }

    fg_save_knowledge_categories($dataset, 'Update knowledge category', $context + ['knowledge_category_id' => $categoryId]);

    return $updated;
}
