<?php

require_once __DIR__ . '/load_knowledge_base.php';
require_once __DIR__ . '/save_knowledge_base.php';
require_once __DIR__ . '/default_knowledge_base_dataset.php';
require_once __DIR__ . '/normalize_knowledge_slug.php';
require_once __DIR__ . '/load_knowledge_categories.php';
require_once __DIR__ . '/default_knowledge_categories_dataset.php';
require_once __DIR__ . '/get_setting.php';

function fg_add_knowledge_article(array $input, array $context = []): array
{
    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('A title is required for knowledge base articles.');
    }

    $summary = trim((string) ($input['summary'] ?? ''));
    $content = trim((string) ($input['content'] ?? ''));
    $rawSlug = trim((string) ($input['slug'] ?? $title));
    $slug = fg_normalize_knowledge_slug($rawSlug);

    $status = strtolower(trim((string) ($input['status'] ?? 'published')));
    if (!in_array($status, ['draft', 'scheduled', 'published', 'archived'], true)) {
        $status = 'published';
    }

    $visibility = strtolower(trim((string) ($input['visibility'] ?? 'public')));
    if (!in_array($visibility, ['public', 'members', 'private'], true)) {
        $visibility = 'public';
    }

    $template = trim((string) ($input['template'] ?? 'article'));
    if ($template === '') {
        $template = 'article';
    }

    $authorUserId = $input['author_user_id'] ?? null;
    if ($authorUserId !== null) {
        $authorUserId = (int) $authorUserId;
        if ($authorUserId <= 0) {
            $authorUserId = null;
        }
    }

    $categoryId = $input['category_id'] ?? null;
    if ($categoryId !== null) {
        $categoryId = (int) $categoryId;
        if ($categoryId <= 0) {
            $categoryId = null;
        }
    }

    try {
        $categoryDataset = fg_load_knowledge_categories();
    } catch (Throwable $exception) {
        $categoryDataset = fg_default_knowledge_categories_dataset();
    }

    $availableCategories = $categoryDataset['records'] ?? [];
    if (!is_array($availableCategories)) {
        $availableCategories = [];
    }

    if ($categoryId !== null) {
        $categoryExists = false;
        foreach ($availableCategories as $category) {
            if ((int) ($category['id'] ?? 0) === $categoryId) {
                $categoryExists = true;
                break;
            }
        }
        if (!$categoryExists) {
            $categoryId = null;
        }
    }

    if ($categoryId === null) {
        $defaultCategory = $context['default_category_id'] ?? null;
        if ($defaultCategory === null) {
            $defaultCategory = (int) fg_get_setting('knowledge_base_default_category', 0);
            if ($defaultCategory <= 0) {
                $defaultCategory = null;
            }
        }
        if ($defaultCategory !== null) {
            foreach ($availableCategories as $category) {
                if ((int) ($category['id'] ?? 0) === (int) $defaultCategory) {
                    $categoryId = (int) $defaultCategory;
                    break;
                }
            }
        }
        if ($categoryId === null && !empty($availableCategories)) {
            $first = $availableCategories[0];
            if (isset($first['id'])) {
                $categoryId = (int) $first['id'];
            }
        }
    }

    $tagsInput = $input['tags'] ?? [];
    if (!is_array($tagsInput)) {
        $tagsInput = preg_split('/[,\n]+/', (string) $tagsInput) ?: [];
    }
    $tags = [];
    foreach ($tagsInput as $tag) {
        $normalized = strtolower(trim((string) $tag));
        if ($normalized !== '' && !in_array($normalized, $tags, true)) {
            $tags[] = $normalized;
        }
    }

    $attachments = $input['attachments'] ?? [];
    if (!is_array($attachments)) {
        $attachments = [];
    }
    $attachments = array_values(array_filter(array_map('trim', $attachments), static function ($value) {
        return $value !== '';
    }));

    try {
        $dataset = fg_load_knowledge_base();
    } catch (Throwable $exception) {
        $dataset = fg_default_knowledge_base_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_knowledge_base_dataset();
    }

    $nextId = (int) ($dataset['next_id'] ?? 1);
    if ($nextId < 1) {
        $nextId = 1;
    }

    $now = date(DATE_ATOM);
    $record = [
        'id' => $nextId,
        'slug' => $slug,
        'title' => $title,
        'summary' => $summary,
        'content' => $content,
        'tags' => $tags,
        'category_id' => $categoryId,
        'visibility' => $visibility,
        'status' => $status,
        'template' => $template,
        'author_user_id' => $authorUserId,
        'attachments' => $attachments,
        'created_at' => $now,
        'updated_at' => $now,
    ];

    $dataset['records'][] = $record;
    $dataset['next_id'] = $nextId + 1;

    fg_save_knowledge_base($dataset, 'Create knowledge article', $context + ['knowledge_article_id' => $nextId]);

    return $record;
}
