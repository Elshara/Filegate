<?php

require_once __DIR__ . '/load_knowledge_categories.php';
require_once __DIR__ . '/default_knowledge_categories_dataset.php';

function fg_list_knowledge_categories(array $viewer = []): array
{
    try {
        $dataset = fg_load_knowledge_categories();
    } catch (Throwable $exception) {
        $dataset = fg_default_knowledge_categories_dataset();
    }

    $records = $dataset['records'] ?? [];
    if (!is_array($records)) {
        return [];
    }

    $role = strtolower((string) ($viewer['role'] ?? ''));
    $isMember = !empty($viewer);
    $canModerate = in_array($role, ['admin', 'moderator'], true);

    $result = [];
    foreach ($records as $record) {
        if (!is_array($record)) {
            continue;
        }
        $visibility = strtolower((string) ($record['visibility'] ?? 'public'));
        if ($visibility === 'private' && !$canModerate) {
            continue;
        }
        if ($visibility === 'members' && !$isMember) {
            continue;
        }
        $result[] = $record;
    }

    usort($result, static function (array $a, array $b) {
        $orderA = (int) ($a['ordering'] ?? 0);
        $orderB = (int) ($b['ordering'] ?? 0);
        if ($orderA === $orderB) {
            return strcmp(strtolower((string) ($a['name'] ?? '')), strtolower((string) ($b['name'] ?? '')));
        }
        return $orderA <=> $orderB;
    });

    return $result;
}
