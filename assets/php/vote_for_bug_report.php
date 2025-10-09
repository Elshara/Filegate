<?php

require_once __DIR__ . '/load_bug_reports.php';
require_once __DIR__ . '/save_bug_reports.php';
require_once __DIR__ . '/default_bug_reports_dataset.php';

function fg_vote_for_bug_report(int $id, int $userId, string $mode = 'toggle'): array
{
    if ($id <= 0 || $userId <= 0) {
        throw new InvalidArgumentException('Valid bug report and user identifiers are required.');
    }

    try {
        $dataset = fg_load_bug_reports();
    } catch (Throwable $exception) {
        $dataset = fg_default_bug_reports_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_bug_reports_dataset();
    }

    $index = null;
    foreach ($dataset['records'] as $key => $record) {
        if ((int) ($record['id'] ?? 0) === $id) {
            $index = $key;
            break;
        }
    }

    if ($index === null) {
        throw new RuntimeException('Bug report not found.');
    }

    $record = $dataset['records'][$index];
    $watchers = $record['watchers'] ?? [];
    if (!is_array($watchers)) {
        $watchers = [];
    }

    $watchers = array_map('intval', $watchers);
    $watchers = array_values(array_filter($watchers, static function ($value) {
        return $value > 0;
    }));

    $hasWatch = in_array($userId, $watchers, true);
    $normalizedMode = strtolower($mode);
    if ($normalizedMode === 'unwatch') {
        $watchers = array_values(array_filter($watchers, static function ($value) use ($userId) {
            return (int) $value !== $userId;
        }));
    } elseif ($normalizedMode === 'watch') {
        if (!$hasWatch) {
            $watchers[] = $userId;
        }
    } else {
        if ($hasWatch) {
            $watchers = array_values(array_filter($watchers, static function ($value) use ($userId) {
                return (int) $value !== $userId;
            }));
        } else {
            $watchers[] = $userId;
        }
    }

    if (!empty($record['reporter_user_id'])) {
        $reporter = (int) $record['reporter_user_id'];
        if ($reporter > 0 && !in_array($reporter, $watchers, true)) {
            $watchers[] = $reporter;
        }
    }

    $record['watchers'] = array_values(array_unique($watchers));
    $record['vote_count'] = count($record['watchers']);
    $record['last_activity_at'] = date(DATE_ATOM);
    $record['updated_at'] = $record['last_activity_at'];

    $dataset['records'][$index] = $record;

    fg_save_bug_reports($dataset, 'Update bug report watchers', [
        'trigger' => 'bug_report_watch',
        'performed_by' => $userId,
        'record_id' => $id,
    ]);

    return $record;
}
