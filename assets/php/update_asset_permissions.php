<?php

require_once __DIR__ . '/load_asset_configurations.php';
require_once __DIR__ . '/save_asset_configurations.php';
require_once __DIR__ . '/is_mirrored_asset.php';
require_once __DIR__ . '/normalize_asset_identifier.php';

function fg_update_asset_permissions(string $asset, array $allowed_roles, bool $allow_user_override): void
{
    $assetKey = fg_normalize_asset_identifier($asset);
    $configurations = fg_load_asset_configurations();
    if (!isset($configurations['records'][$assetKey])) {
        return;
    }

    if (fg_is_mirrored_asset($assetKey)) {
        $configurations['records'][$assetKey]['allowed_roles'] = ['admin'];
        $configurations['records'][$assetKey]['allow_user_override'] = false;
        fg_save_asset_configurations($configurations);
        return;
    }

    $configurations['records'][$assetKey]['allowed_roles'] = array_values(array_unique($allowed_roles));
    $configurations['records'][$assetKey]['allow_user_override'] = $allow_user_override;
    $parameters = $configurations['records'][$assetKey]['parameters'] ?? [];
    foreach ($parameters as $key => $definition) {
        $baseline = $definition['baseline_allow_user_override'] ?? ($definition['allow_user_override'] ?? false);
        $parameters[$key]['allow_user_override'] = $allow_user_override ? $baseline : false;
    }
    $configurations['records'][$assetKey]['parameters'] = $parameters;

    fg_save_asset_configurations($configurations);
}
