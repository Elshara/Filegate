<?php

require_once __DIR__ . '/load_asset_overrides.php';
require_once __DIR__ . '/save_asset_overrides.php';

function fg_clear_asset_override(string $asset, string $scope, string $identifier): void
{
    $overrides = fg_load_asset_overrides();
    if ($scope === 'global') {
        if (isset($overrides['records']['global'][$asset])) {
            unset($overrides['records']['global'][$asset]);
        }
    } elseif (isset($overrides['records'][$scope][$identifier][$asset])) {
        unset($overrides['records'][$scope][$identifier][$asset]);
        if (empty($overrides['records'][$scope][$identifier])) {
            unset($overrides['records'][$scope][$identifier]);
        }
    }

    fg_save_asset_overrides($overrides);
}
