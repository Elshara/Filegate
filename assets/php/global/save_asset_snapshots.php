<?php

require_once __DIR__ . '/save_json.php';

function fg_save_asset_snapshots(array $snapshots): void
{
    fg_save_json(
        'asset_snapshots',
        $snapshots,
        'Snapshot dataset update',
        ['dataset' => 'asset_snapshots']
    );
}

