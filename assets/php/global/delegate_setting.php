<?php

require_once __DIR__ . '/load_settings.php';
require_once __DIR__ . '/save_settings.php';
require_once __DIR__ . '/seed_defaults.php';

function fg_delegate_setting(string $key, string $managed_by, array $allowed, array $user): bool
{
    fg_seed_defaults();
    if (($user['role'] ?? 'member') !== 'admin') {
        return false;
    }

    $settings = fg_load_settings();
    if (!isset($settings['settings'][$key])) {
        return false;
    }

    $settings['settings'][$key]['managed_by'] = $managed_by;
    $settings['settings'][$key]['allowed_roles'] = $allowed;
    fg_save_settings($settings);

    return true;
}

