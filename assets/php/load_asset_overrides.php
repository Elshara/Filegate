<?php

require_once __DIR__ . '/load_json.php';

function fg_load_asset_overrides(): array
{
    $overrides = fg_load_json('asset_overrides');
    if (!isset($overrides['records']) || !is_array($overrides['records'])) {
        $overrides['records'] = [
            'global' => [],
            'roles' => [],
            'users' => [],
        ];
    }

    foreach (['global', 'roles', 'users'] as $scope) {
        if (!isset($overrides['records'][$scope]) || !is_array($overrides['records'][$scope])) {
            $overrides['records'][$scope] = [];
        }
    }

    return $overrides;
}
