<?php

require_once __DIR__ . '/load_changelog.php';
require_once __DIR__ . '/save_changelog.php';
require_once __DIR__ . '/default_changelog_dataset.php';

function fg_delete_changelog_entry(int $entryId): void
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

    $initialCount = is_array($dataset['records'] ?? null) ? count($dataset['records']) : 0;
    if ($initialCount === 0) {
        throw new RuntimeException('No changelog entries exist to delete.');
    }

    $records = [];
    $deleted = false;
    foreach ($dataset['records'] as $record) {
        if ((int) ($record['id'] ?? 0) === $entryId) {
            $deleted = true;
            continue;
        }
        $records[] = $record;
    }

    if (!$deleted) {
        throw new RuntimeException('The requested changelog entry could not be found.');
    }

    $dataset['records'] = $records;
    $maxId = 0;
    foreach ($records as $record) {
        $maxId = max($maxId, (int) ($record['id'] ?? 0));
    }
    $dataset['next_id'] = max($dataset['next_id'] ?? 1, $maxId + 1);

    fg_save_changelog($dataset, 'Delete changelog entry', [
        'trigger' => 'setup_ui',
        'record_id' => $entryId,
    ]);
}

