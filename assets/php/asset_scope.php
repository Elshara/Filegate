<?php

function fg_asset_scope(string $relative_path): string
{
    $normalized = str_replace('\\', '/', $relative_path);

    if (strpos($normalized, 'assets/') === 0 && strpos($normalized, '/global/') !== false) {
        return 'global';
    }

    if (strpos($normalized, 'assets/php/public/') === 0) {
        return 'global';
    }

    if (strpos($normalized, 'assets/') === 0 && strpos($normalized, '/pages/') !== false) {
        return 'local';
    }

    return 'global';
}
