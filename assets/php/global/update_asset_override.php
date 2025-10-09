<?php

require_once __DIR__ . '/load_asset_overrides.php';
require_once __DIR__ . '/save_asset_overrides.php';
require_once __DIR__ . '/is_mirrored_asset.php';

function fg_update_asset_override(string $asset, string $scope, string $identifier, array $values): void
{
    if (fg_is_mirrored_asset($asset)) {
        return;
    }

    $overrides = fg_load_asset_overrides();
    if (!isset($overrides['records'][$scope])) {
        $overrides['records'][$scope] = [];
    }

    if ($scope === 'users') {
        $identifier = (string) $identifier;
    }

    if ($scope === 'global') {
        $overrides['records']['global'][$asset] = $values;
    } else {
        if (!isset($overrides['records'][$scope][$identifier]) || !is_array($overrides['records'][$scope][$identifier])) {
            $overrides['records'][$scope][$identifier] = [];
        }

        $overrides['records'][$scope][$identifier][$asset] = $values;
    }

    fg_save_asset_overrides($overrides);
}
