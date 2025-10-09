<?php

require_once __DIR__ . '/load_settings.php';
require_once __DIR__ . '/save_settings.php';
require_once __DIR__ . '/seed_defaults.php';
require_once __DIR__ . '/can_manage_setting.php';

function fg_update_setting(string $key, $value, array $user): bool
{
    fg_seed_defaults();
    if (!fg_can_manage_setting($user, $key)) {
        return false;
    }

    $settings = fg_load_settings();
    if (!isset($settings['settings'][$key])) {
        return false;
    }

    $settings['settings'][$key]['value'] = $value;
    fg_save_settings($settings);

    return true;
}

