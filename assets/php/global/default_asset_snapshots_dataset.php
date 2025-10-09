<?php

function fg_default_asset_snapshots_dataset(): array
{
    return [
        'records' => [],
        'next_id' => 1,
        'metadata' => [
            'limit' => 200,
            'per_dataset_limit' => 25,
        ],
    ];
}

