<?php

require_once __DIR__ . '/load_users.php';
require_once __DIR__ . '/save_users.php';

function fg_clear_user_theme(array $user): array
{
    $users = fg_load_users();
    $records = $users['records'] ?? [];

    foreach ($records as $index => $existing) {
        if ((int) ($existing['id'] ?? 0) === (int) ($user['id'] ?? 0)) {
            unset($existing['theme_preferences']);
            $records[$index] = $existing;
            $user = $existing;
            break;
        }
    }

    $users['records'] = $records;
    fg_save_users($users);

    return $user;
}

