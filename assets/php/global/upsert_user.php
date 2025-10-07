<?php

require_once __DIR__ . '/load_users.php';
require_once __DIR__ . '/save_users.php';

function fg_upsert_user(array $user): array
{
    $users = fg_load_users();
    $updated = false;

    foreach ($users['records'] as $index => $existing) {
        if ((int) $existing['id'] === (int) ($user['id'] ?? 0)) {
            $users['records'][$index] = array_merge($existing, $user);
            $user = $users['records'][$index];
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        $user['id'] = $users['next_id'] ?? 1;
        $users['next_id'] = ($users['next_id'] ?? 1) + 1;
        $users['records'][] = $user;
    }

    fg_save_users($users);

    return $user;
}

