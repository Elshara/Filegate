<?php

require_once __DIR__ . '/load_asset_configurations.php';

function fg_is_mirrored_asset(string $asset): bool
{
    $configurations = fg_load_asset_configurations();
    $record = $configurations['records'][$asset] ?? null;
    if (!is_array($record)) {
        return false;
    }

    $mirrorOf = $record['mirror_of'] ?? null;
    return is_string($mirrorOf) && $mirrorOf !== '' && $mirrorOf !== $asset;
}
