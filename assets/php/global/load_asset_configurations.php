<?php

require_once __DIR__ . '/load_json.php';

function fg_load_asset_configurations(): array
{
    $configurations = fg_load_json('asset_configurations');
    if (!isset($configurations['records']) || !is_array($configurations['records'])) {
        $configurations['records'] = [];
    }

    return $configurations;
}
