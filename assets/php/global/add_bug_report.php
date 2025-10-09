<?php

require_once __DIR__ . '/load_bug_reports.php';
require_once __DIR__ . '/save_bug_reports.php';
require_once __DIR__ . '/default_bug_reports_dataset.php';
require_once __DIR__ . '/get_setting.php';

function fg_add_bug_report(array $input): array
{
    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('A title is required when submitting a bug report.');
    }

    $summary = trim((string) ($input['summary'] ?? ''));
    $details = trim((string) ($input['details'] ?? ''));

    $statusOptions = fg_get_setting('bug_report_statuses', ['new', 'triaged', 'in_progress', 'resolved', 'wont_fix', 'duplicate']);
    if (!is_array($statusOptions) || empty($statusOptions)) {
        $statusOptions = ['new'];
    }
    $statusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $statusOptions)));
    if (empty($statusOptions)) {
        $statusOptions = ['new'];
    }

    $status = strtolower(trim((string) ($input['status'] ?? $statusOptions[0])));
    if (!in_array($status, $statusOptions, true)) {
        $status = $statusOptions[0];
    }

    $severityOptions = fg_get_setting('bug_report_severities', ['low', 'medium', 'high', 'critical']);
    if (!is_array($severityOptions) || empty($severityOptions)) {
        $severityOptions = ['medium'];
    }
    $severityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $severityOptions)));
    if (empty($severityOptions)) {
        $severityOptions = ['medium'];
    }

    $severity = strtolower(trim((string) ($input['severity'] ?? $severityOptions[0])));
    if (!in_array($severity, $severityOptions, true)) {
        $severity = $severityOptions[0];
    }

    $visibilityDefault = strtolower((string) fg_get_setting('bug_report_default_visibility', 'members'));
    if (!in_array($visibilityDefault, ['public', 'members', 'private'], true)) {
        $visibilityDefault = 'members';
    }
    $visibility = strtolower(trim((string) ($input['visibility'] ?? $visibilityDefault)));
    if (!in_array($visibility, ['public', 'members', 'private'], true)) {
        $visibility = $visibilityDefault;
    }

    $reporterUserId = $input['reporter_user_id'] ?? null;
    if ($reporterUserId !== null) {
        $reporterUserId = (int) $reporterUserId;
        if ($reporterUserId <= 0) {
            $reporterUserId = null;
        }
    }

    $ownerRole = trim((string) ($input['owner_role'] ?? ''));
    if ($ownerRole === '') {
        $ownerRole = null;
    }

    $ownerUserId = $input['owner_user_id'] ?? null;
    if ($ownerUserId !== null) {
        $ownerUserId = (int) $ownerUserId;
        if ($ownerUserId <= 0) {
            $ownerUserId = null;
        }
    }

    $tagsInput = $input['tags'] ?? [];
    if (!is_array($tagsInput)) {
        $tagsInput = array_filter(array_map('trim', preg_split('/[,\n]+/', (string) $tagsInput) ?: []));
    }
    $tags = [];
    foreach ($tagsInput as $tag) {
        $normalized = strtolower(trim((string) $tag));
        if ($normalized !== '') {
            $tags[] = $normalized;
        }
    }

    $stepsInput = $input['steps_to_reproduce'] ?? [];
    if (!is_array($stepsInput)) {
        $stepsInput = array_filter(array_map('trim', preg_split('/\r?\n/', (string) $stepsInput) ?: []));
    }
    $steps = [];
    foreach ($stepsInput as $step) {
        $normalized = trim((string) $step);
        if ($normalized !== '') {
            $steps[] = $normalized;
        }
    }

    $versionsInput = $input['affected_versions'] ?? [];
    if (!is_array($versionsInput)) {
        $versionsInput = array_filter(array_map('trim', preg_split('/[,\n]+/', (string) $versionsInput) ?: []));
    }
    $versions = [];
    foreach ($versionsInput as $version) {
        $normalized = trim((string) $version);
        if ($normalized !== '') {
            $versions[] = $normalized;
        }
    }

    $referenceInput = $input['reference_links'] ?? [];
    if (!is_array($referenceInput)) {
        $referenceInput = array_filter(array_map('trim', preg_split('/\r?\n/', (string) $referenceInput) ?: []));
    }
    $referenceLinks = [];
    foreach ($referenceInput as $link) {
        $normalized = trim((string) $link);
        if ($normalized !== '') {
            $referenceLinks[] = $normalized;
        }
    }

    $attachmentsInput = $input['attachments'] ?? [];
    if (!is_array($attachmentsInput)) {
        $attachmentsInput = array_filter(array_map('trim', preg_split('/[,\n]+/', (string) $attachmentsInput) ?: []));
    }
    $attachments = [];
    foreach ($attachmentsInput as $attachment) {
        $normalized = trim((string) $attachment);
        if ($normalized !== '') {
            $attachments[] = $normalized;
        }
    }

    $environment = trim((string) ($input['environment'] ?? ''));
    $resolutionNotes = trim((string) ($input['resolution_notes'] ?? ''));

    $watchersInput = $input['watchers'] ?? [];
    if (!is_array($watchersInput)) {
        $watchersInput = preg_split('/[,\n]+/', (string) $watchersInput) ?: [];
    }
    $watchers = [];
    foreach ($watchersInput as $watcher) {
        $value = (int) $watcher;
        if ($value > 0) {
            $watchers[] = $value;
        }
    }
    if ($reporterUserId) {
        $watchers[] = $reporterUserId;
    }
    $watchers = array_values(array_unique($watchers));

    try {
        $dataset = fg_load_bug_reports();
    } catch (Throwable $exception) {
        $dataset = fg_default_bug_reports_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_bug_reports_dataset();
    }

    $nextId = (int) ($dataset['next_id'] ?? 1);
    if ($nextId <= 0) {
        $nextId = 1;
    }

    $now = date(DATE_ATOM);
    $record = [
        'id' => $nextId,
        'title' => $title,
        'summary' => $summary,
        'details' => $details,
        'status' => $status,
        'severity' => $severity,
        'visibility' => $visibility,
        'reporter_user_id' => $reporterUserId,
        'owner_role' => $ownerRole,
        'owner_user_id' => $ownerUserId,
        'tags' => $tags,
        'steps_to_reproduce' => $steps,
        'affected_versions' => $versions,
        'environment' => $environment,
        'watchers' => $watchers,
        'vote_count' => count($watchers),
        'reference_links' => $referenceLinks,
        'attachments' => $attachments,
        'resolution_notes' => $resolutionNotes,
        'created_at' => $now,
        'updated_at' => $now,
        'last_activity_at' => $now,
    ];

    $dataset['records'][] = $record;
    $dataset['next_id'] = $nextId + 1;

    fg_save_bug_reports($dataset, 'Create bug report', [
        'trigger' => $input['trigger'] ?? 'bug_report_created',
        'performed_by' => $input['performed_by'] ?? null,
        'record_id' => $record['id'],
    ]);

    return $record;
}
