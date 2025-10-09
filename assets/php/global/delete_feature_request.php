<?php

require_once __DIR__ . '/load_feature_requests.php';
require_once __DIR__ . '/save_feature_requests.php';
require_once __DIR__ . '/default_feature_requests_dataset.php';

function fg_delete_feature_request(int $id, array $context = []): void
{
    if ($id <= 0) {
        throw new InvalidArgumentException('A valid feature request identifier is required for deletion.');
    }

    try {
        $dataset = fg_load_feature_requests();
    } catch (Throwable $exception) {
        $dataset = fg_default_feature_requests_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_feature_requests_dataset();
    }

    $found = false;
    foreach ($dataset['records'] as $key => $record) {
        if ((int) ($record['id'] ?? 0) === $id) {
            unset($dataset['records'][$key]);
            $found = true;
            break;
        }
    }

    if (!$found) {
        throw new RuntimeException('Feature request not found.');
    }

    $dataset['records'] = array_values($dataset['records']);

    $context = array_merge([
        'trigger' => 'feature_request_delete',
        'record_id' => $id,
    ], $context);

    fg_save_feature_requests($dataset, 'Delete feature request', $context);
}

