<?php

require_once __DIR__ . '/load_json.php';
require_once __DIR__ . '/default_activity_log_dataset.php';

function fg_load_activity_log(): array
{
    $log = fg_load_json('activity_log');
    if (!isset($log['records']) || !is_array($log['records'])) {
        $log = fg_default_activity_log_dataset();
    }

    $log['next_id'] = (int) ($log['next_id'] ?? 1);

    if (!isset($log['metadata']) || !is_array($log['metadata'])) {
        $log['metadata'] = ['limit' => 500];
    }

    return $log;
}
