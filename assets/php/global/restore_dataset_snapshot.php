<?php

require_once __DIR__ . '/load_asset_snapshots.php';
require_once __DIR__ . '/save_dataset_contents.php';
require_once __DIR__ . '/record_activity_event.php';

function fg_restore_dataset_snapshot(string $dataset, int $snapshotId, array $context = []): void
{
    $snapshots = fg_load_asset_snapshots();
    $records = $snapshots['records'] ?? [];

    foreach ($records as $record) {
        if ((int) ($record['id'] ?? 0) !== $snapshotId) {
            continue;
        }
        if (($record['dataset'] ?? '') !== $dataset) {
            continue;
        }

        $payload = (string) ($record['payload'] ?? '');
        if ($payload === '') {
            throw new RuntimeException('Snapshot payload is empty and cannot be restored.');
        }

        $metadata = $context;
        if (!isset($metadata['trigger'])) {
            $metadata['trigger'] = 'snapshot_restore';
        }
        $metadata['snapshot_id'] = $snapshotId;
        $metadata['snapshot_reason'] = $record['reason'] ?? '';

        fg_save_dataset_contents($dataset, $payload, 'Snapshot restore', $metadata);
        $activityContext = $context;
        if (!is_array($activityContext)) {
            $activityContext = [];
        }
        $activityContext['dataset'] = $dataset;
        if (!isset($activityContext['trigger'])) {
            $activityContext['trigger'] = 'snapshot_restore';
        }

        fg_record_activity_event('snapshot', 'restored', [
            'dataset' => $dataset,
            'snapshot_id' => $snapshotId,
            'reason' => $record['reason'] ?? '',
            'payload_hash' => sha1($payload),
        ], $activityContext);
        return;
    }

    throw new InvalidArgumentException('Snapshot not found for the requested dataset.');
}

