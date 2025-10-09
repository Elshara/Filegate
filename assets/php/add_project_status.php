<?php

require_once __DIR__ . '/load_project_status.php';
require_once __DIR__ . '/save_project_status.php';
require_once __DIR__ . '/default_project_status_dataset.php';

function fg_add_project_status(array $input): void
{
    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('A title is required when recording roadmap progress.');
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

    try {
        $dataset = fg_load_project_status();
        if (!isset($dataset['records']) || !is_array($dataset['records'])) {
            $dataset = fg_default_project_status_dataset();
        }
    } catch (Throwable $exception) {
        $dataset = fg_default_project_status_dataset();
    }

    $nextId = (int) ($dataset['next_id'] ?? 1);
    if ($nextId < 1) {
        $nextId = 1;
    }

    $now = date('c');
    $record = [
        'id' => $nextId,
        'title' => $title,
        'summary' => $summary,
        'status' => $status,
        'category' => $category,
        'owner_role' => $ownerRole,
        'owner_user_id' => $ownerUserId,
        'milestone' => $milestone,
        'progress' => $progress,
        'links' => $normalizedLinks,
        'created_at' => $now,
        'updated_at' => $now,
    ];

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset['records'] = [];
    }

    $dataset['records'][] = $record;
    $dataset['next_id'] = $nextId + 1;

    fg_save_project_status($dataset, 'Create project status entry', [
        'trigger' => 'setup_ui',
        'record_id' => $record['id'],
    ]);
}

