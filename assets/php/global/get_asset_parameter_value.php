<?php

require_once __DIR__ . '/load_asset_configurations.php';
require_once __DIR__ . '/load_asset_overrides.php';

function fg_get_asset_parameter_value(string $asset, string $parameter, array $context = [])
{
    $configurations = fg_load_asset_configurations();
    $overrides = fg_load_asset_overrides();

    $definition = $configurations['records'][$asset]['parameters'][$parameter] ?? null;
    $value = $definition['default'] ?? null;

    if (isset($overrides['records']['global'][$asset][$parameter])) {
        $value = $overrides['records']['global'][$asset][$parameter];
    }

    $role = $context['role'] ?? null;
    if ($role && isset($overrides['records']['roles'][$role][$asset][$parameter])) {
        $value = $overrides['records']['roles'][$role][$asset][$parameter];
    }

    $userId = $context['user_id'] ?? null;
    if ($userId && isset($overrides['records']['users'][(string) $userId][$asset][$parameter])) {
        $value = $overrides['records']['users'][(string) $userId][$asset][$parameter];
    }

    return $value;
}
