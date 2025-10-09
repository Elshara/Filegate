<?php

require_once __DIR__ . '/load_json.php';
require_once __DIR__ . '/default_asset_snapshots_dataset.php';

function fg_load_asset_snapshots(): array
{
    $snapshots = fg_load_json('asset_snapshots');
    if (!is_array($snapshots) || empty($snapshots)) {
        return fg_default_asset_snapshots_dataset();
    }

    if (!isset($snapshots['records']) || !is_array($snapshots['records'])) {
        $snapshots['records'] = [];
    }

    if (!isset($snapshots['metadata']) || !is_array($snapshots['metadata'])) {
        $snapshots['metadata'] = fg_default_asset_snapshots_dataset()['metadata'];
    } else {
        $defaults = fg_default_asset_snapshots_dataset()['metadata'];
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $snapshots['metadata'])) {
                $snapshots['metadata'][$key] = $value;
            }
        }
    }

    if (!isset($snapshots['next_id']) || !is_numeric($snapshots['next_id'])) {
        $snapshots['next_id'] = 1;
    }

    return $snapshots;
}

