<?php

require_once __DIR__ . '/load_asset_configurations.php';
require_once __DIR__ . '/normalize_asset_identifier.php';

function fg_is_mirrored_asset(string $asset): bool
{
    $configurations = fg_load_asset_configurations();
    $assetKey = fg_normalize_asset_identifier($asset);
    $record = $configurations['records'][$assetKey] ?? null;
    if (!is_array($record)) {
        return false;
    }

    $mirrorOf = $record['mirror_of'] ?? null;
    return is_string($mirrorOf) && $mirrorOf !== '' && $mirrorOf !== $assetKey;
}
