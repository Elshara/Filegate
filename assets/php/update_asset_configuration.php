<?php

require_once __DIR__ . '/load_asset_configurations.php';
require_once __DIR__ . '/save_asset_configurations.php';
require_once __DIR__ . '/normalize_asset_identifier.php';

function fg_update_asset_configuration(string $asset, array $changes): void
{
    $assetKey = fg_normalize_asset_identifier($asset);
    $configurations = fg_load_asset_configurations();
    if (!isset($configurations['records'][$assetKey])) {
        return;
    }

    $parameters = $configurations['records'][$assetKey]['parameters'] ?? [];
    foreach ($changes as $key => $value) {
        if (!isset($parameters[$key])) {
            continue;
        }
        $parameters[$key]['default'] = $value;
    }
    $configurations['records'][$assetKey]['parameters'] = $parameters;

    fg_save_asset_configurations($configurations);
}
