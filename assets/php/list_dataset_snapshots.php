<?php

require_once __DIR__ . '/load_asset_snapshots.php';

function fg_list_dataset_snapshots(string $dataset, int $limit = 10): array
{
    $snapshots = fg_load_asset_snapshots();
    $records = $snapshots['records'] ?? [];
    $filtered = [];

    foreach ($records as $record) {
        if (($record['dataset'] ?? '') !== $dataset) {
            continue;
        }
        $filtered[] = $record;
        if ($limit > 0 && count($filtered) >= $limit) {
            break;
        }
    }

    return $filtered;
}

