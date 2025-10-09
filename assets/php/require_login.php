<?php

require_once __DIR__ . '/current_user.php';

function fg_require_login(): array
{
    $user = fg_current_user();
    if ($user === null) {
        header('Location: /login.php');
        exit;
    }

    return $user;
}

