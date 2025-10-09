<?php

require_once __DIR__ . '/load_changelog.php';
require_once __DIR__ . '/save_changelog.php';
require_once __DIR__ . '/default_changelog_dataset.php';

function fg_update_changelog_entry(int $entryId, array $input): void
{
    if ($entryId <= 0) {
        throw new InvalidArgumentException('A valid changelog entry identifier is required.');
    }

    try {
        $dataset = fg_load_changelog();
        if (!isset($dataset['records']) || !is_array($dataset['records'])) {
            $dataset = fg_default_changelog_dataset();
        }
    } catch (Throwable $exception) {
        $dataset = fg_default_changelog_dataset();
    }

    $found = false;
    foreach ($dataset['records'] as $index => $record) {
        if ((int) ($record['id'] ?? 0) !== $entryId) {
            continue;
        }

        $found = true;
        $title = trim((string) ($input['title'] ?? ($record['title'] ?? '')));
        if ($title === '') {
            throw new InvalidArgumentException('A title is required when updating changelog notes.');
        }

        $summary = trim((string) ($input['summary'] ?? ($record['summary'] ?? '')));
        $type = strtolower(trim((string) ($input['type'] ?? ($record['type'] ?? 'announcement'))));
        $allowedTypes = ['release', 'improvement', 'fix', 'announcement', 'breaking'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'announcement';
        }

        $visibility = strtolower(trim((string) ($input['visibility'] ?? ($record['visibility'] ?? 'public'))));
        $allowedVisibility = ['public', 'members', 'private'];
        if (!in_array($visibility, $allowedVisibility, true)) {
            $visibility = 'public';
        }

        $highlight = !empty($input['highlight']);
        if (!array_key_exists('highlight', $input)) {
            $highlight = !empty($record['highlight']);
        }

        $body = trim((string) ($input['body'] ?? ($record['body'] ?? '')));

        $tagsInput = $input['tags'] ?? ($record['tags'] ?? []);
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

        $linksInput = $input['links'] ?? ($record['links'] ?? []);
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

        $relatedInput = $input['related_project_status_ids'] ?? ($record['related_project_status_ids'] ?? []);
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

        $publishedAt = $record['published_at'] ?? null;
        if (array_key_exists('published_at', $input)) {
            $publishedAtInput = trim((string) $input['published_at']);
            if ($publishedAtInput === '') {
                $publishedAt = null;
            } else {
                $timestamp = strtotime($publishedAtInput);
                if ($timestamp !== false) {
                    $publishedAt = date('c', $timestamp);
                }
            }
        }

        $dataset['records'][$index] = [
            'id' => $entryId,
            'title' => $title,
            'summary' => $summary,
            'type' => $type,
            'tags' => $tags,
            'visibility' => $visibility,
            'highlight' => $highlight,
            'body' => $body,
            'links' => $links,
            'related_project_status_ids' => $relatedIds,
            'created_at' => $record['created_at'] ?? date('c'),
            'updated_at' => date('c'),
            'published_at' => $publishedAt,
        ];
        break;
    }

    if (!$found) {
        throw new RuntimeException('The requested changelog entry could not be found.');
    }

    fg_save_changelog($dataset, 'Update changelog entry', [
        'trigger' => 'setup_ui',
        'record_id' => $entryId,
    ]);
}

