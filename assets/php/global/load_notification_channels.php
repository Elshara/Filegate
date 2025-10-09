<?php

require_once __DIR__ . '/load_json.php';

function fg_load_notification_channels(): array
{
    $channels = fg_load_json('notification_channels');
    return is_array($channels) ? $channels : [];
}

