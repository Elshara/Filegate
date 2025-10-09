<?php

require_once __DIR__ . '/save_json.php';

function fg_save_feature_requests(array $dataset, ?string $reason = null, array $context = []): void
{
    fg_save_json('feature_requests', $dataset, $reason ?? 'Save feature request dataset', $context);
}

