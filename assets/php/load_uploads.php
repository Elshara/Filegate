<?php

require_once __DIR__ . '/load_json.php';

function fg_load_uploads(): array
{
    $uploads = fg_load_json('uploads');
    if (!isset($uploads['records']) || !is_array($uploads['records'])) {
        return ['records' => [], 'next_id' => 1];
    }

    $uploads['next_id'] = (int) ($uploads['next_id'] ?? 1);

    return $uploads;
}

