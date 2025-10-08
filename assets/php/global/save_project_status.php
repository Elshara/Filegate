<?php

require_once __DIR__ . '/save_json.php';

function fg_save_project_status(array $status, ?string $reason = null, array $context = []): void
{
    fg_save_json('project_status', $status, $reason, $context);
}

