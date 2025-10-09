<?php

require_once __DIR__ . '/load_asset_snapshots.php';
require_once __DIR__ . '/save_asset_snapshots.php';
require_once __DIR__ . '/record_activity_event.php';

function fg_delete_dataset_snapshot(int $snapshotId, ?string $dataset = null, array $context = []): bool
{
    $snapshots = fg_load_asset_snapshots();
    $records = $snapshots['records'] ?? [];
    $updated = [];
    $removed = false;
    $removedRecord = null;

    foreach ($records as $record) {
        if ((int) ($record['id'] ?? 0) === $snapshotId && ($dataset === null || ($record['dataset'] ?? '') === $dataset)) {
            $removed = true;
            $removedRecord = $record;
            continue;
        }
        $updated[] = $record;
    }

    if ($removed) {
        $snapshots['records'] = $updated;
        fg_save_asset_snapshots($snapshots);

        $activityContext = $context;
        if (!is_array($activityContext)) {
            $activityContext = [];
        }
        $activityContext['dataset'] = $dataset ?? ($removedRecord['dataset'] ?? null);
        if (!isset($activityContext['trigger'])) {
            $activityContext['trigger'] = 'snapshot_delete';
        }

        fg_record_activity_event('snapshot', 'deleted', [
            'dataset' => $dataset ?? ($removedRecord['dataset'] ?? ''),
            'snapshot_id' => $snapshotId,
            'reason' => $removedRecord['reason'] ?? '',
        ], $activityContext);
    }

    return $removed;
}

