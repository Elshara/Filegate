<?php

require_once __DIR__ . '/load_knowledge_categories.php';
require_once __DIR__ . '/save_knowledge_categories.php';
require_once __DIR__ . '/default_knowledge_categories_dataset.php';
require_once __DIR__ . '/normalize_knowledge_category_slug.php';

function fg_add_knowledge_category(array $input, array $context = []): array
{
    $name = trim((string) ($input['name'] ?? ''));
    if ($name === '') {
        throw new InvalidArgumentException('A name is required for knowledge base categories.');
    }

    $slug = fg_normalize_knowledge_category_slug((string) ($input['slug'] ?? $name));
    $description = trim((string) ($input['description'] ?? ''));

    $visibility = strtolower(trim((string) ($input['visibility'] ?? 'public')));
    if (!in_array($visibility, ['public', 'members', 'private'], true)) {
        $visibility = 'public';
    }

    $ordering = (int) ($input['ordering'] ?? 0);
    if ($ordering < 0) {
        $ordering = 0;
    }

    try {
        $dataset = fg_load_knowledge_categories();
    } catch (Throwable $exception) {
        $dataset = fg_default_knowledge_categories_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_knowledge_categories_dataset();
    }

    $nextId = (int) ($dataset['next_id'] ?? 1);
    if ($nextId < 1) {
        $nextId = 1;
    }

    $record = [
        'id' => $nextId,
        'slug' => $slug,
        'name' => $name,
        'description' => $description,
        'visibility' => $visibility,
        'ordering' => $ordering,
    ];

    $dataset['records'][] = $record;
    $dataset['next_id'] = $nextId + 1;

    fg_save_knowledge_categories($dataset, 'Create knowledge category', $context + ['knowledge_category_id' => $nextId]);

    return $record;
}
