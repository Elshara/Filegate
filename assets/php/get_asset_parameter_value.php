<?php

require_once __DIR__ . '/load_asset_configurations.php';
require_once __DIR__ . '/load_asset_overrides.php';
require_once __DIR__ . '/normalize_asset_identifier.php';

function fg_get_asset_parameter_value(string $asset, string $parameter, array $context = [])
{
    $configurations = fg_load_asset_configurations();
    $overrides = fg_load_asset_overrides();

    $candidateKeys = array_values(array_unique([
        $asset,
        fg_normalize_asset_identifier($asset),
    ]));

    $recordKey = null;
    foreach ($candidateKeys as $candidate) {
        if (isset($configurations['records'][$candidate])) {
            $recordKey = $candidate;
            break;
        }
    }

    if ($recordKey === null) {
        return null;
    }

    $record = $configurations['records'][$recordKey];
    $mirrorOf = $record['mirror_of'] ?? null;

    if (is_string($mirrorOf) && $mirrorOf !== '' && $mirrorOf !== $asset) {
        $baseValue = fg_get_asset_parameter_value($mirrorOf, $parameter, $context);
        $definition = $record['parameters'][$parameter] ?? [];

        if ($parameter === 'enabled') {
            $localDefault = $definition['default'] ?? true;
            if ($localDefault === false) {
                return false;
            }
        }

        return $baseValue;
    }

    $definition = $record['parameters'][$parameter] ?? null;
    $value = $definition['default'] ?? null;

    if (isset($overrides['records']['global'][$recordKey][$parameter])) {
        $value = $overrides['records']['global'][$recordKey][$parameter];
    }

    $role = $context['role'] ?? null;
    if ($role && isset($overrides['records']['roles'][$role][$recordKey][$parameter])) {
        $value = $overrides['records']['roles'][$role][$recordKey][$parameter];
    }

    $userId = $context['user_id'] ?? null;
    if ($userId && isset($overrides['records']['users'][(string) $userId][$recordKey][$parameter])) {
        $value = $overrides['records']['users'][(string) $userId][$recordKey][$parameter];
    }

    return $value;
}
