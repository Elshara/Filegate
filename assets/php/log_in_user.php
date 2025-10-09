<?php

require_once __DIR__ . '/start_session.php';

function fg_log_in_user(int $user_id): void
{
    fg_start_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
}

