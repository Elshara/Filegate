<?php

require_once __DIR__ . '/load_json.php';
require_once __DIR__ . '/default_bug_reports_dataset.php';

function fg_load_bug_reports(): array
{
    try {
        $dataset = fg_load_json('bug_reports');
    } catch (Throwable $exception) {
        $dataset = fg_default_bug_reports_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_bug_reports_dataset();
    }

    return $dataset;
}
