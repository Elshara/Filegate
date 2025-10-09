<?php

require_once __DIR__ . '/load_json.php';
require_once __DIR__ . '/default_polls_dataset.php';

function fg_load_polls(): array
{
    try {
        $dataset = fg_load_json('polls');
    } catch (Throwable $exception) {
        $dataset = fg_default_polls_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_polls_dataset();
    }

    return $dataset;
}
