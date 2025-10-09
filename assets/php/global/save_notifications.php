<?php

require_once __DIR__ . '/save_json.php';

function fg_save_notifications(array $notifications): void
{
    fg_save_json('notifications', $notifications);
}

