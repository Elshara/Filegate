<?php

require_once __DIR__ . '/load_feature_requests.php';
require_once __DIR__ . '/save_feature_requests.php';
require_once __DIR__ . '/default_feature_requests_dataset.php';

function fg_vote_for_feature_request(int $id, int $userId, string $mode = 'toggle'): array
{
    if ($id <= 0 || $userId <= 0) {
        throw new InvalidArgumentException('A valid feature request and user identifier are required.');
    }

    try {
        $dataset = fg_load_feature_requests();
    } catch (Throwable $exception) {
        $dataset = fg_default_feature_requests_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_feature_requests_dataset();
    }

    $index = null;
    foreach ($dataset['records'] as $key => $record) {
        if ((int) ($record['id'] ?? 0) === $id) {
            $index = $key;
            break;
        }
    }

    if ($index === null) {
        throw new RuntimeException('Feature request not found.');
    }

    $record = $dataset['records'][$index];
    $supporters = $record['supporters'] ?? [];
    if (!is_array($supporters)) {
        $supporters = [];
    }

    $supporters = array_map('intval', $supporters);
    $supporters = array_values(array_filter($supporters, static function ($value) {
        return $value > 0;
    }));

    $hasSupport = in_array($userId, $supporters, true);
    $normalizedMode = strtolower($mode);
    if ($normalizedMode === 'withdraw') {
        $supporters = array_values(array_filter($supporters, static function ($value) use ($userId) {
            return (int) $value !== $userId;
        }));
    } elseif ($normalizedMode === 'support') {
        if (!$hasSupport) {
            $supporters[] = $userId;
        }
    } else { // toggle
        if ($hasSupport) {
            $supporters = array_values(array_filter($supporters, static function ($value) use ($userId) {
                return (int) $value !== $userId;
            }));
        } else {
            $supporters[] = $userId;
        }
    }

    $record['supporters'] = array_values(array_unique($supporters));
    $record['vote_count'] = count($record['supporters']);
    $record['last_activity_at'] = date(DATE_ATOM);
    $record['updated_at'] = $record['last_activity_at'];

    $dataset['records'][$index] = $record;

    fg_save_feature_requests($dataset, 'Update feature request support', [
        'trigger' => 'feature_request_vote',
        'record_id' => $id,
        'performed_by' => $userId,
    ]);

    return $record;
}

