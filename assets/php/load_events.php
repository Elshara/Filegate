<?php

require_once __DIR__ . '/load_json.php';
require_once __DIR__ . '/default_events_dataset.php';

function fg_load_events(): array
{
    try {
        $dataset = fg_load_json('events');
    } catch (Throwable $exception) {
        $dataset = fg_default_events_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_events_dataset();
    }

    return $dataset;
}
