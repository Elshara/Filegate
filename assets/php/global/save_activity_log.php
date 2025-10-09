<?php

require_once __DIR__ . '/save_json.php';

function fg_save_activity_log(array $log): void
{
    fg_save_json('activity_log', $log, 'Activity log update', [
        'trigger' => 'activity_logger',
    ]);
}
