<?php

require_once __DIR__ . '/load_project_status.php';
require_once __DIR__ . '/save_project_status.php';
require_once __DIR__ . '/default_project_status_dataset.php';

function fg_delete_project_status(int $id): void
{
    if ($id <= 0) {
        throw new InvalidArgumentException('A valid project status identifier is required for deletion.');
    }

    try {
        $dataset = fg_load_project_status();
        if (!isset($dataset['records']) || !is_array($dataset['records'])) {
            $dataset = fg_default_project_status_dataset();
        }
    } catch (Throwable $exception) {
        $dataset = fg_default_project_status_dataset();
    }

    $removed = false;
    $records = [];
    foreach ($dataset['records'] as $record) {
        if ((int) ($record['id'] ?? 0) === $id) {
            $removed = true;
            continue;
        }
        $records[] = $record;
    }

    if (!$removed) {
        throw new RuntimeException('Unable to locate the requested project status entry for deletion.');
    }

    $dataset['records'] = $records;

    fg_save_project_status($dataset, 'Delete project status entry', [
        'trigger' => 'setup_ui',
        'record_id' => $id,
    ]);
}

