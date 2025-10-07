<?php

require_once __DIR__ . '/load_asset_mirrors.php';
require_once __DIR__ . '/get_asset_parameter_value.php';

function fg_sync_public_assets(): void
{
    $mirrors = fg_load_asset_mirrors();
    if (empty($mirrors)) {
        return;
    }

    $root = realpath(__DIR__ . '/../../..');
    if ($root === false) {
        return;
    }

    foreach ($mirrors as $mirror) {
        $source = $mirror['source'];
        $target = $mirror['target'];
        $sourcePath = $root . '/' . ltrim($source, '/');
        $targetPath = $root . '/' . ltrim($target, '/');

        if (!is_file($sourcePath)) {
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }
            continue;
        }

        $enabled = fg_get_asset_parameter_value($source, 'enabled');
        if ($enabled === false) {
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }
            continue;
        }

        $mirrorEnabled = fg_get_asset_parameter_value($target, 'enabled');
        if ($mirrorEnabled === false) {
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }
            continue;
        }

        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            continue;
        }

        if (!is_file($targetPath) || filemtime($sourcePath) > filemtime($targetPath)) {
            @copy($sourcePath, $targetPath);
        }
    }
}
