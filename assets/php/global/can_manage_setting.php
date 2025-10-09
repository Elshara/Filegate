<?php

require_once __DIR__ . '/load_settings.php';
require_once __DIR__ . '/seed_defaults.php';

function fg_can_manage_setting(?array $user, string $setting_key): bool
{
    fg_seed_defaults();
    $settings = fg_load_settings();
    if (!isset($settings['settings'][$setting_key])) {
        return false;
    }

    $definition = $settings['settings'][$setting_key];
    $managed_by = $definition['managed_by'] ?? 'admins';

    if ($managed_by === 'none') {
        return false;
    }

    if ($managed_by === 'everyone') {
        return true;
    }

    if ($user === null) {
        return false;
    }

    $role = $user['role'] ?? 'member';

    if ($managed_by === 'admins') {
        return $role === 'admin';
    }

    if ($managed_by === 'custom') {
        $allowed = $definition['allowed_roles'] ?? [];
        if (in_array($role, $allowed, true)) {
            return true;
        }
        $user_key = 'user:' . $user['id'];
        return in_array($user_key, $allowed, true);
    }

    return false;
}

