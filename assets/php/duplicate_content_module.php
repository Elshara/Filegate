<?php

require_once __DIR__ . '/load_content_modules.php';
require_once __DIR__ . '/save_content_modules.php';
require_once __DIR__ . '/normalize_content_module.php';
require_once __DIR__ . '/normalize_content_module_key.php';

function fg_duplicate_content_module(int $moduleId, array $overrides = []): ?array
{
    $modules = fg_load_content_modules();
    if (!isset($modules['records']) || !is_array($modules['records'])) {
        return null;
    }

    $records = $modules['records'];
    $original = null;
    foreach ($records as $index => $record) {
        if ((int) ($record['id'] ?? 0) === $moduleId) {
            $original = $record;
            break;
        }
    }

    if ($original === null) {
        return null;
    }

    if (!isset($modules['next_id']) || (int) $modules['next_id'] <= 0) {
        $maxId = 0;
        foreach ($records as $record) {
            $maxId = max($maxId, (int) ($record['id'] ?? 0));
        }
        $modules['next_id'] = $maxId + 1;
    }

    $newId = (int) $modules['next_id'];
    if ($newId <= 0) {
        $newId = 1;
    }
    $modules['next_id'] = $newId + 1;

    $normalizedOriginal = fg_normalize_content_module_definition($original);

    $existingKeys = array_map(static function ($record) {
        return (string) ($record['key'] ?? '');
    }, $records);

    $existingLabels = array_map(static function ($record) {
        return (string) ($record['label'] ?? '');
    }, $records);

    $labelOverride = trim((string) ($overrides['label'] ?? ''));
    if ($labelOverride === '') {
        $labelBase = $normalizedOriginal['label'] !== '' ? $normalizedOriginal['label'] . ' (Copy)' : 'Content module copy';
        $label = $labelBase;
        $labelSuffix = 2;
        while (in_array($label, $existingLabels, true)) {
            $label = $labelBase . ' ' . $labelSuffix;
            $labelSuffix++;
        }
    } else {
        $label = $labelOverride;
    }

    $keyOverride = trim((string) ($overrides['key'] ?? ''));
    $keySource = $keyOverride !== '' ? $keyOverride : ($normalizedOriginal['key'] !== '' ? $normalizedOriginal['key'] . '-copy' : $label);
    $keyBase = fg_normalize_content_module_key($keySource);
    if ($keyBase === '') {
        $keyBase = 'module';
    }
    $key = $keyBase;
    $keySuffix = 2;
    while (in_array($key, $existingKeys, true)) {
        $key = $keyBase . '-' . $keySuffix;
        $keySuffix++;
    }

    $dataset = trim((string) ($overrides['dataset'] ?? ($normalizedOriginal['dataset'] ?? 'posts')));
    if ($dataset === '') {
        $dataset = 'posts';
    }

    $format = trim((string) ($overrides['format'] ?? ($normalizedOriginal['format'] ?? '')));
    $description = trim((string) ($overrides['description'] ?? ($normalizedOriginal['description'] ?? '')));

    $statusDefault = $normalizedOriginal['status'] === 'archived' ? 'archived' : 'draft';
    $status = strtolower(trim((string) ($overrides['status'] ?? $statusDefault)));
    if (!in_array($status, ['active', 'draft', 'archived'], true)) {
        $status = 'draft';
    }

    $visibility = strtolower(trim((string) ($overrides['visibility'] ?? ($normalizedOriginal['visibility'] ?? 'members'))));
    if (!in_array($visibility, ['everyone', 'members', 'admins'], true)) {
        $visibility = 'members';
    }

    $allowedRoles = $normalizedOriginal['allowed_roles'] ?? [];
    if (!is_array($allowedRoles)) {
        $allowedRoles = [];
    }
    $allowedRoles = array_values(array_filter(array_map(static function ($role) {
        return strtolower(trim((string) $role));
    }, $allowedRoles), static function ($role) {
        return $role !== '';
    }));

    $newRecord = $original;
    $newRecord['id'] = $newId;
    $newRecord['key'] = $key;
    $newRecord['label'] = $label;
    $newRecord['dataset'] = $dataset;
    $newRecord['format'] = $format;
    $newRecord['description'] = $description;
    $newRecord['status'] = $status;
    $newRecord['visibility'] = $visibility;
    $newRecord['allowed_roles'] = $allowedRoles;

    $listKeys = ['categories', 'fields', 'profile_prompts', 'wizard_steps', 'css_tokens', 'micro_guides', 'macro_guides'];
    foreach ($listKeys as $listKey) {
        if (!isset($newRecord[$listKey])) {
            continue;
        }
        if (is_array($newRecord[$listKey])) {
            $newRecord[$listKey] = array_values($newRecord[$listKey]);
        } else {
            $newRecord[$listKey] = $newRecord[$listKey];
        }
    }

    if (isset($newRecord['tasks']) && is_array($newRecord['tasks'])) {
        $resetTasks = [];
        foreach ($newRecord['tasks'] as $task) {
            if (is_array($task)) {
                $taskCopy = $task;
                $taskCopy['completed'] = false;
                unset($taskCopy['completed_at'], $taskCopy['completed_on']);
                $resetTasks[] = $taskCopy;
            } else {
                $resetTasks[] = $task;
            }
        }
        $newRecord['tasks'] = $resetTasks;
    }

    if (isset($newRecord['guides']) && is_array($newRecord['guides'])) {
        $newRecord['guides'] = [
            'micro' => array_values($newRecord['guides']['micro'] ?? []),
            'macro' => array_values($newRecord['guides']['macro'] ?? []),
        ];
    }

    foreach (['task_progress', 'usage', 'analytics', 'last_activity', 'last_used_at'] as $transientKey) {
        if (array_key_exists($transientKey, $newRecord)) {
            unset($newRecord[$transientKey]);
        }
    }

    if (isset($newRecord['relationships']) && is_array($newRecord['relationships'])) {
        $relationships = [];
        foreach ($newRecord['relationships'] as $relationship) {
            if (!is_array($relationship)) {
                continue;
            }
            $relationships[] = [
                'type' => strtolower(trim((string) ($relationship['type'] ?? 'related'))),
                'module_key' => trim((string) ($relationship['module_key'] ?? ($relationship['module_reference'] ?? ''))),
                'module_label' => trim((string) ($relationship['module_label'] ?? '')),
                'module_reference' => trim((string) ($relationship['module_reference'] ?? '')),
                'description' => trim((string) ($relationship['description'] ?? '')),
            ];
        }
        $newRecord['relationships'] = $relationships;
    }

    $inserted = false;
    $updatedRecords = [];
    foreach ($records as $record) {
        $updatedRecords[] = $record;
        if (!$inserted && (int) ($record['id'] ?? 0) === $moduleId) {
            $updatedRecords[] = $newRecord;
            $inserted = true;
        }
    }

    if (!$inserted) {
        $updatedRecords[] = $newRecord;
    }

    $modules['records'] = array_values($updatedRecords);
    $modules['metadata'] = $modules['metadata'] ?? [];
    $modules['metadata']['record_count'] = count($modules['records']);

    fg_save_content_modules($modules);

    return $newRecord;
}
