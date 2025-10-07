<?php

function fg_asset_scope(string $relative_path): string
{
    $normalized = str_replace('\\', '/', $relative_path);

    if (strpos($normalized, 'assets/') === 0 && strpos($normalized, '/global/') !== false) {
        return 'global';
    }

    $global_public = [
        'public/index.php',
        'public/login.php',
        'public/logout.php',
        'public/settings.php',
        'public/setup.php',
        'public/dataset.php',
        'public/media.php',
        'public/toggle-like.php',
    ];

    if (in_array($normalized, $global_public, true)) {
        return 'global';
    }

    if (strpos($normalized, 'assets/') === 0 && strpos($normalized, '/pages/') !== false) {
        return 'local';
    }

    if (strpos($normalized, 'public/assets/') === 0) {
        return 'local';
    }

    if (strpos($normalized, 'public/') === 0) {
        return 'local';
    }

    return 'global';
}
