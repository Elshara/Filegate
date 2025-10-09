<?php

require_once __DIR__ . '/load_json.php';
require_once __DIR__ . '/default_feature_requests_dataset.php';

function fg_load_feature_requests(): array
{
    try {
        $dataset = fg_load_json('feature_requests');
    } catch (Throwable $exception) {
        $dataset = fg_default_feature_requests_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_feature_requests_dataset();
    }

    return $dataset;
}

