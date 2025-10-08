<?php

require_once __DIR__ . '/load_changelog.php';
require_once __DIR__ . '/save_changelog.php';
require_once __DIR__ . '/default_changelog_dataset.php';

function fg_add_changelog_entry(array $input): void
{
    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('A title is required when recording changelog notes.');
    }

    $summary = trim((string) ($input['summary'] ?? ''));
    $type = strtolower(trim((string) ($input['type'] ?? 'release')));
    $allowedTypes = ['release', 'improvement', 'fix', 'announcement', 'breaking'];
    if (!in_array($type, $allowedTypes, true)) {
        $type = 'announcement';
    }

    $visibility = strtolower(trim((string) ($input['visibility'] ?? 'public')));
    $allowedVisibility = ['public', 'members', 'private'];
    if (!in_array($visibility, $allowedVisibility, true)) {
        $visibility = 'public';
    }

    $highlight = !empty($input['highlight']);
    $body = trim((string) ($input['body'] ?? ''));

    $tagsInput = $input['tags'] ?? [];
    if (!is_array($tagsInput)) {
        $tagsInput = preg_split('/\s*,\s*/', (string) $tagsInput);
    }
    $tags = [];
    foreach ($tagsInput as $tag) {
        $normalized = strtolower(trim((string) $tag));
        if ($normalized !== '') {
            $tags[] = $normalized;
        }
    }
    $tags = array_values(array_unique($tags));

    $linksInput = $input['links'] ?? [];
    if (!is_array($linksInput)) {
        $linksInput = preg_split('/\r?\n/', (string) $linksInput);
    }
    $links = [];
    foreach ($linksInput as $link) {
        $normalized = trim((string) $link);
        if ($normalized !== '') {
            $links[] = $normalized;
        }
    }

    $relatedInput = $input['related_project_status_ids'] ?? [];
    if (!is_array($relatedInput)) {
        $relatedInput = preg_split('/\s*,\s*/', (string) $relatedInput);
    }
    $relatedIds = [];
    foreach ($relatedInput as $value) {
        $id = (int) $value;
        if ($id > 0) {
            $relatedIds[] = $id;
        }
    }
    $relatedIds = array_values(array_unique($relatedIds));

    $publishedAtInput = trim((string) ($input['published_at'] ?? ''));
    $now = date('c');
    $publishedAt = null;
    if ($publishedAtInput !== '') {
        $timestamp = strtotime($publishedAtInput);
        if ($timestamp !== false) {
            $publishedAt = date('c', $timestamp);
        }
    }

    try {
        $dataset = fg_load_changelog();
        if (!isset($dataset['records']) || !is_array($dataset['records'])) {
            $dataset = fg_default_changelog_dataset();
        }
    } catch (Throwable $exception) {
        $dataset = fg_default_changelog_dataset();
    }

    $nextId = (int) ($dataset['next_id'] ?? 1);
    if ($nextId < 1) {
        $nextId = 1;
    }

    $record = [
        'id' => $nextId,
        'title' => $title,
        'summary' => $summary,
        'type' => $type,
        'tags' => $tags,
        'visibility' => $visibility,
        'highlight' => $highlight,
        'body' => $body,
        'links' => $links,
        'related_project_status_ids' => $relatedIds,
        'created_at' => $now,
        'updated_at' => $now,
        'published_at' => $publishedAt,
    ];

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset['records'] = [];
    }

    $dataset['records'][] = $record;
    $dataset['next_id'] = $nextId + 1;

    fg_save_changelog($dataset, 'Create changelog entry', [
        'trigger' => 'setup_ui',
        'record_id' => $record['id'],
    ]);
}

