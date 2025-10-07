<?php

require_once __DIR__ . '/load_users.php';

function fg_find_user_by_id(int $user_id): ?array
{
    $users = fg_load_users();
    foreach ($users['records'] as $user) {
        if ((int) $user['id'] === $user_id) {
            return $user;
        }
    }

    return null;
}

