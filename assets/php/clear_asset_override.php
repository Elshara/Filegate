<?php

require_once __DIR__ . '/load_asset_overrides.php';
require_once __DIR__ . '/save_asset_overrides.php';
require_once __DIR__ . '/normalize_asset_identifier.php';

function fg_clear_asset_override(string $asset, string $scope, string $identifier): void
{
    $assetKey = fg_normalize_asset_identifier($asset);
    $overrides = fg_load_asset_overrides();
    if ($scope === 'global') {
        if (isset($overrides['records']['global'][$assetKey])) {
            unset($overrides['records']['global'][$assetKey]);
        }
    } elseif (isset($overrides['records'][$scope][$identifier][$assetKey])) {
        unset($overrides['records'][$scope][$identifier][$assetKey]);
        if (empty($overrides['records'][$scope][$identifier])) {
            unset($overrides['records'][$scope][$identifier]);
        }
    }

    fg_save_asset_overrides($overrides);
}
