<?php

require_once __DIR__ . '/load_users.php';

function fg_find_user_by_username(string $username): ?array
{
    $users = fg_load_users();
    foreach ($users['records'] as $user) {
        if (strcasecmp($user['username'], $username) === 0) {
            return $user;
        }
    }

    return null;
}

