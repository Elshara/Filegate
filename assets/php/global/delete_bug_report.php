<?php

require_once __DIR__ . '/load_bug_reports.php';
require_once __DIR__ . '/save_bug_reports.php';
require_once __DIR__ . '/default_bug_reports_dataset.php';

function fg_delete_bug_report(int $id, array $context = []): void
{
    if ($id <= 0) {
        throw new InvalidArgumentException('A valid bug report identifier is required.');
    }

    try {
        $dataset = fg_load_bug_reports();
    } catch (Throwable $exception) {
        $dataset = fg_default_bug_reports_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_bug_reports_dataset();
    }

    $removed = false;
    $records = [];
    foreach ($dataset['records'] as $record) {
        if ((int) ($record['id'] ?? 0) === $id) {
            $removed = true;
            continue;
        }
        $records[] = $record;
    }

    if (!$removed) {
        throw new RuntimeException('Bug report not found.');
    }

    $dataset['records'] = $records;

    fg_save_bug_reports($dataset, 'Delete bug report', [
        'trigger' => $context['trigger'] ?? 'bug_report_deleted',
        'performed_by' => $context['performed_by'] ?? null,
        'record_id' => $id,
    ]);
}
