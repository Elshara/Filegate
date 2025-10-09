<?php

require_once __DIR__ . '/load_json.php';

function fg_load_notifications(): array
{
    $notifications = fg_load_json('notifications');
    if (!isset($notifications['records']) || !is_array($notifications['records'])) {
        return ['records' => [], 'next_id' => 1];
    }

    $notifications['next_id'] = (int) ($notifications['next_id'] ?? 1);

    return $notifications;
}

