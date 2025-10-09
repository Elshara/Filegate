<?php

require_once __DIR__ . '/load_knowledge_base.php';
require_once __DIR__ . '/save_knowledge_base.php';
require_once __DIR__ . '/default_knowledge_base_dataset.php';
require_once __DIR__ . '/normalize_knowledge_slug.php';
require_once __DIR__ . '/load_knowledge_categories.php';
require_once __DIR__ . '/default_knowledge_categories_dataset.php';

function fg_update_knowledge_article(int $articleId, array $input, array $context = []): ?array
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

    $updated = null;
    foreach ($dataset['records'] as $index => $record) {
        if (!is_array($record) || (int) ($record['id'] ?? 0) !== $articleId) {
            continue;
        }

        $title = trim((string) ($input['title'] ?? $record['title'] ?? 'Untitled article'));
        if ($title === '') {
            $title = 'Untitled article';
        }

        $summary = trim((string) ($input['summary'] ?? $record['summary'] ?? ''));
        $content = trim((string) ($input['content'] ?? $record['content'] ?? ''));
        $slugSource = trim((string) ($input['slug'] ?? $record['slug'] ?? $title));
        $slug = fg_normalize_knowledge_slug($slugSource);

        $status = strtolower(trim((string) ($input['status'] ?? $record['status'] ?? 'draft')));
        if (!in_array($status, ['draft', 'scheduled', 'published', 'archived'], true)) {
            $status = $record['status'] ?? 'draft';
        }

        $visibility = strtolower(trim((string) ($input['visibility'] ?? $record['visibility'] ?? 'public')));
        if (!in_array($visibility, ['public', 'members', 'private'], true)) {
            $visibility = $record['visibility'] ?? 'public';
        }

        $template = trim((string) ($input['template'] ?? $record['template'] ?? 'article'));
        if ($template === '') {
            $template = 'article';
        }

        $authorUserId = $input['author_user_id'] ?? ($record['author_user_id'] ?? null);
        if ($authorUserId !== null) {
            $authorUserId = (int) $authorUserId;
            if ($authorUserId <= 0) {
                $authorUserId = null;
            }
        }

        $categoryId = $input['category_id'] ?? ($record['category_id'] ?? null);
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

        $tagsInput = $input['tags'] ?? ($record['tags'] ?? []);
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

        $attachmentsInput = $input['attachments'] ?? ($record['attachments'] ?? []);
        if (!is_array($attachmentsInput)) {
            $attachmentsInput = [];
        }
        $attachments = array_values(array_filter(array_map('trim', $attachmentsInput), static function ($value) {
            return $value !== '';
        }));

        $now = date(DATE_ATOM);
        $dataset['records'][$index] = [
            'id' => $articleId,
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
            'created_at' => $record['created_at'] ?? $now,
            'updated_at' => $now,
        ];

        $updated = $dataset['records'][$index];
        break;
    }

    if ($updated === null) {
        return null;
    }

    fg_save_knowledge_base($dataset, 'Update knowledge article', $context + ['knowledge_article_id' => $articleId]);

    return $updated;
}
