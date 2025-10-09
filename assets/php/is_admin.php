<?php

require_once __DIR__ . '/current_user.php';

function fg_is_admin(): bool
{
    $user = fg_current_user();
    return $user !== null && ($user['role'] ?? 'member') === 'admin';
}

