<?php

require_once __DIR__ . '/load_settings.php';
require_once __DIR__ . '/seed_defaults.php';

function fg_get_setting(string $key, $default = null)
{
    fg_seed_defaults();
    $settings = fg_load_settings();
    return $settings['settings'][$key]['value'] ?? $default;
}

