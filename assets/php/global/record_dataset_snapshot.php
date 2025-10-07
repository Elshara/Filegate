<?php

require_once __DIR__ . '/load_asset_snapshots.php';
require_once __DIR__ . '/save_asset_snapshots.php';
require_once __DIR__ . '/dataset_format.php';
require_once __DIR__ . '/dataset_path.php';
require_once __DIR__ . '/load_dataset_contents.php';
require_once __DIR__ . '/record_activity_event.php';

function fg_record_dataset_snapshot(string $dataset, string $reason, array $context = [], ?string $payload = null): bool
{
    $path = fg_dataset_path($dataset);
    if (!file_exists($path)) {
        return false;
    }

    if ($payload === null) {
        $payload = fg_load_dataset_contents($dataset);
    }

    $snapshots = fg_load_asset_snapshots();
    if (!isset($snapshots['records']) || !is_array($snapshots['records'])) {
        $snapshots['records'] = [];
    }

    foreach ($snapshots['records'] as $existing) {
        if (($existing['dataset'] ?? '') !== $dataset) {
            continue;
        }
        if ((string) ($existing['payload'] ?? '') === (string) $payload) {
            return false;
        }
        break;
    }

    $nextId = (int) ($snapshots['next_id'] ?? 1);
    $record = [
        'id' => $nextId,
        'dataset' => $dataset,
        'created_at' => gmdate('c'),
        'reason' => $reason,
        'context' => $context,
        'format' => fg_dataset_format($dataset),
        'payload' => (string) $payload,
    ];

    array_unshift($snapshots['records'], $record);
    $snapshots['next_id'] = $nextId + 1;

    $metadata = $snapshots['metadata'] ?? [];
    $limit = (int) ($metadata['limit'] ?? 0);
    $perDatasetLimit = (int) ($metadata['per_dataset_limit'] ?? 0);

    if ($perDatasetLimit > 0) {
        $filtered = [];
        $counts = [];
        foreach ($snapshots['records'] as $entry) {
            $key = $entry['dataset'] ?? '';
            $counts[$key] = ($counts[$key] ?? 0) + 1;
            if ($counts[$key] <= $perDatasetLimit) {
                $filtered[] = $entry;
            }
        }
        $snapshots['records'] = $filtered;
    }

    if ($limit > 0 && count($snapshots['records']) > $limit) {
        $snapshots['records'] = array_slice($snapshots['records'], 0, $limit);
    }

    fg_save_asset_snapshots($snapshots);

    $activityContext = $context;
    if (!is_array($activityContext)) {
        $activityContext = [];
    }
    if (!isset($activityContext['dataset'])) {
        $activityContext['dataset'] = $dataset;
    }
    if (!isset($activityContext['trigger'])) {
        $activityContext['trigger'] = 'snapshot_record';
    }

    fg_record_activity_event('snapshot', 'recorded', [
        'dataset' => $dataset,
        'snapshot_id' => $record['id'],
        'reason' => $reason,
        'format' => $record['format'],
        'payload_hash' => sha1((string) $payload),
    ], $activityContext);

    return true;
}

