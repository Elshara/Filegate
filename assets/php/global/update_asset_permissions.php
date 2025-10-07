<?php

require_once __DIR__ . '/load_asset_configurations.php';
require_once __DIR__ . '/save_asset_configurations.php';

function fg_update_asset_permissions(string $asset, array $allowed_roles, bool $allow_user_override): void
{
    $configurations = fg_load_asset_configurations();
    if (!isset($configurations['records'][$asset])) {
        return;
    }

    $configurations['records'][$asset]['allowed_roles'] = array_values(array_unique($allowed_roles));
    $configurations['records'][$asset]['allow_user_override'] = $allow_user_override;
    $parameters = $configurations['records'][$asset]['parameters'] ?? [];
    foreach ($parameters as $key => $definition) {
        $baseline = $definition['baseline_allow_user_override'] ?? ($definition['allow_user_override'] ?? false);
        $parameters[$key]['allow_user_override'] = $allow_user_override ? $baseline : false;
    }
    $configurations['records'][$asset]['parameters'] = $parameters;

    fg_save_asset_configurations($configurations);
}
