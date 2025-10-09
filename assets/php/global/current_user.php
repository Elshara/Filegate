<?php

require_once __DIR__ . '/start_session.php';
require_once __DIR__ . '/load_users.php';

function fg_current_user(): ?array
{
    fg_start_session();
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $users = fg_load_users();
    foreach ($users['records'] as $user) {
        if ((int) $user['id'] === (int) $_SESSION['user_id']) {
            return $user;
        }
    }

    return null;
}

