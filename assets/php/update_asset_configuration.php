<?php

require_once __DIR__ . '/load_asset_configurations.php';
require_once __DIR__ . '/save_asset_configurations.php';

function fg_update_asset_configuration(string $asset, array $changes): void
{
    $configurations = fg_load_asset_configurations();
    if (!isset($configurations['records'][$asset])) {
        return;
    }

    $parameters = $configurations['records'][$asset]['parameters'] ?? [];
    foreach ($changes as $key => $value) {
        if (!isset($parameters[$key])) {
            continue;
        }
        $parameters[$key]['default'] = $value;
    }
    $configurations['records'][$asset]['parameters'] = $parameters;

    fg_save_asset_configurations($configurations);
}
