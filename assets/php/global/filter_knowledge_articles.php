<?php

require_once __DIR__ . '/load_knowledge_base.php';
require_once __DIR__ . '/normalize_knowledge_slug.php';

function fg_filter_knowledge_articles(array $viewer, array $filters = []): array
{
    $dataset = fg_load_knowledge_base();
    $records = $dataset['records'] ?? [];
    if (!is_array($records)) {
        return [];
    }

    $role = strtolower((string) ($viewer['role'] ?? ''));
    $isMember = !empty($viewer);
    $canModerate = in_array($role, ['admin', 'moderator'], true);

    $filters = array_change_key_case($filters, CASE_LOWER);
    $filterCategoryId = null;
    if (isset($filters['category_id'])) {
        $filterCategoryId = (int) $filters['category_id'];
        if ($filterCategoryId <= 0) {
            $filterCategoryId = null;
        }
    }

    $filterTag = null;
    if (isset($filters['tag'])) {
        $filterTag = strtolower(trim((string) $filters['tag']));
        if ($filterTag === '') {
            $filterTag = null;
        }
    }

    $filterQuery = null;
    if (isset($filters['query'])) {
        $filterQuery = strtolower(trim((string) $filters['query']));
        if ($filterQuery === '') {
            $filterQuery = null;
        }
    }

    $filtered = [];
    foreach ($records as $record) {
        if (!is_array($record)) {
            continue;
        }
        $status = strtolower((string) ($record['status'] ?? 'draft'));
        if ($status === 'draft' && !$canModerate) {
            continue;
        }
        if ($status === 'archived' && !$canModerate) {
            continue;
        }

        $visibility = strtolower((string) ($record['visibility'] ?? 'public'));
        if ($visibility === 'private' && !$canModerate) {
            continue;
        }
        if ($visibility === 'members' && !$isMember) {
            continue;
        }

        $record['slug'] = fg_normalize_knowledge_slug((string) ($record['slug'] ?? ''));
        if ($filterCategoryId !== null) {
            if ((int) ($record['category_id'] ?? 0) !== $filterCategoryId) {
                continue;
            }
        }

        if ($filterTag !== null) {
            $tags = $record['tags'] ?? [];
            if (!is_array($tags)) {
                continue;
            }
            $normalizedTags = array_map('strtolower', $tags);
            if (!in_array($filterTag, $normalizedTags, true)) {
                continue;
            }
        }

        if ($filterQuery !== null) {
            $haystack = strtolower((string) ($record['title'] ?? '')) . ' ' .
                strtolower((string) ($record['summary'] ?? '')) . ' ' .
                strtolower(strip_tags((string) ($record['content'] ?? '')));
            if (strpos($haystack, $filterQuery) === false) {
                continue;
            }
        }

        $filtered[] = $record;
    }

    usort($filtered, static function (array $a, array $b) {
        $statusOrder = ['published' => 0, 'scheduled' => 1, 'draft' => 2, 'archived' => 3];
        $statusA = strtolower((string) ($a['status'] ?? 'published'));
        $statusB = strtolower((string) ($b['status'] ?? 'published'));
        $orderA = $statusOrder[$statusA] ?? 4;
        $orderB = $statusOrder[$statusB] ?? 4;
        if ($orderA !== $orderB) {
            return $orderA <=> $orderB;
        }

        $timeA = strtotime((string) ($a['updated_at'] ?? $a['created_at'] ?? 'now'));
        $timeB = strtotime((string) ($b['updated_at'] ?? $b['created_at'] ?? 'now'));
        return $timeB <=> $timeA;
    });

    return $filtered;
}
