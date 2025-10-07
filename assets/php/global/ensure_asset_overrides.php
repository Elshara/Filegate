<?php

require_once __DIR__ . '/load_asset_overrides.php';
require_once __DIR__ . '/save_asset_overrides.php';

function fg_ensure_asset_overrides(): void
{
    $overrides = fg_load_asset_overrides();
    $expected = ['global', 'roles', 'users'];
    $updated = false;

    foreach ($expected as $key) {
        if (!isset($overrides['records'][$key]) || !is_array($overrides['records'][$key])) {
            $overrides['records'][$key] = [];
            $updated = true;
        }
    }

    if ($updated) {
        fg_save_asset_overrides($overrides);
    }
}
