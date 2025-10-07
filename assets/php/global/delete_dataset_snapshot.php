<?php

require_once __DIR__ . '/load_asset_snapshots.php';
require_once __DIR__ . '/save_asset_snapshots.php';

function fg_delete_dataset_snapshot(int $snapshotId, ?string $dataset = null): bool
{
    $snapshots = fg_load_asset_snapshots();
    $records = $snapshots['records'] ?? [];
    $updated = [];
    $removed = false;

    foreach ($records as $record) {
        if ((int) ($record['id'] ?? 0) === $snapshotId && ($dataset === null || ($record['dataset'] ?? '') === $dataset)) {
            $removed = true;
            continue;
        }
        $updated[] = $record;
    }

    if ($removed) {
        $snapshots['records'] = $updated;
        fg_save_asset_snapshots($snapshots);
    }

    return $removed;
}

