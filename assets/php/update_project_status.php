<?php

require_once __DIR__ . '/load_project_status.php';
require_once __DIR__ . '/save_project_status.php';
require_once __DIR__ . '/default_project_status_dataset.php';

function fg_update_project_status(int $id, array $input): void
{
    if ($id <= 0) {
        throw new InvalidArgumentException('A valid project status identifier is required.');
    }

    try {
        $dataset = fg_load_project_status();
        if (!isset($dataset['records']) || !is_array($dataset['records'])) {
            $dataset = fg_default_project_status_dataset();
        }
    } catch (Throwable $exception) {
        $dataset = fg_default_project_status_dataset();
    }

    $index = null;
    foreach ($dataset['records'] as $key => $record) {
        if ((int) ($record['id'] ?? 0) === $id) {
            $index = $key;
            break;
        }
    }

    if ($index === null) {
        throw new RuntimeException('Unable to locate the requested project status entry.');
    }

    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('A title is required when updating roadmap progress.');
    }

    $status = (string) ($input['status'] ?? 'planned');
    $allowedStatuses = ['planned', 'in_progress', 'built', 'on_hold'];
    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'planned';
    }

    $summary = trim((string) ($input['summary'] ?? ''));
    $category = trim((string) ($input['category'] ?? ''));
    $ownerRole = trim((string) ($input['owner_role'] ?? ''));
    if ($ownerRole === '') {
        $ownerRole = null;
    }

    $ownerUserId = $input['owner_user_id'] ?? null;
    if ($ownerUserId !== null) {
        $ownerUserId = (int) $ownerUserId;
        if ($ownerUserId <= 0) {
            $ownerUserId = null;
        }
    }

    $milestone = trim((string) ($input['milestone'] ?? ''));
    $progress = (int) ($input['progress'] ?? 0);
    if ($progress < 0) {
        $progress = 0;
    }
    if ($progress > 100) {
        $progress = 100;
    }

    $links = $input['links'] ?? [];
    if (!is_array($links)) {
        $links = [];
    }
    $normalizedLinks = [];
    foreach ($links as $link) {
        $normalized = trim((string) $link);
        if ($normalized !== '') {
            $normalizedLinks[] = $normalized;
        }
    }

    $record = $dataset['records'][$index];
    $record['title'] = $title;
    $record['summary'] = $summary;
    $record['status'] = $status;
    $record['category'] = $category;
    $record['owner_role'] = $ownerRole;
    $record['owner_user_id'] = $ownerUserId;
    $record['milestone'] = $milestone;
    $record['progress'] = $progress;
    $record['links'] = $normalizedLinks;
    $record['updated_at'] = date('c');

    $dataset['records'][$index] = $record;

    fg_save_project_status($dataset, 'Update project status entry', [
        'trigger' => 'setup_ui',
        'record_id' => $id,
    ]);
}

