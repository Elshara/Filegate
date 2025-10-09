<?php

require_once __DIR__ . '/load_bug_reports.php';
require_once __DIR__ . '/save_bug_reports.php';
require_once __DIR__ . '/default_bug_reports_dataset.php';
require_once __DIR__ . '/get_setting.php';

function fg_update_bug_report(int $id, array $input): array
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

    if (array_key_exists('title', $input)) {
        $title = trim((string) $input['title']);
        if ($title === '') {
            throw new InvalidArgumentException('A bug report title cannot be empty.');
        }
        $record['title'] = $title;
    }

    if (array_key_exists('summary', $input)) {
        $record['summary'] = trim((string) $input['summary']);
    }

    if (array_key_exists('details', $input)) {
        $record['details'] = trim((string) $input['details']);
    }

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

    if (array_key_exists('status', $input)) {
        $status = strtolower(trim((string) $input['status']));
        if (!in_array($status, $statusOptions, true)) {
            $status = $statusOptions[0];
        }
        $record['status'] = $status;
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

    if (array_key_exists('severity', $input)) {
        $severity = strtolower(trim((string) $input['severity']));
        if (!in_array($severity, $severityOptions, true)) {
            $severity = $severityOptions[0];
        }
        $record['severity'] = $severity;
    }

    $visibilityDefault = strtolower((string) fg_get_setting('bug_report_default_visibility', 'members'));
    if (!in_array($visibilityDefault, ['public', 'members', 'private'], true)) {
        $visibilityDefault = 'members';
    }

    if (array_key_exists('visibility', $input)) {
        $visibility = strtolower(trim((string) $input['visibility']));
        if (!in_array($visibility, ['public', 'members', 'private'], true)) {
            $visibility = $visibilityDefault;
        }
        $record['visibility'] = $visibility;
    }

    if (array_key_exists('reporter_user_id', $input)) {
        $reporter = $input['reporter_user_id'];
        if ($reporter !== null) {
            $reporter = (int) $reporter;
            if ($reporter <= 0) {
                $reporter = null;
            }
        }
        $record['reporter_user_id'] = $reporter;
    }

    if (array_key_exists('owner_role', $input)) {
        $ownerRole = trim((string) $input['owner_role']);
        $record['owner_role'] = $ownerRole === '' ? null : $ownerRole;
    }

    if (array_key_exists('owner_user_id', $input)) {
        $ownerUserId = $input['owner_user_id'];
        if ($ownerUserId !== null) {
            $ownerUserId = (int) $ownerUserId;
            if ($ownerUserId <= 0) {
                $ownerUserId = null;
            }
        }
        $record['owner_user_id'] = $ownerUserId;
    }

    if (array_key_exists('tags', $input)) {
        $tagsInput = $input['tags'];
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
        $record['tags'] = $tags;
    }

    if (array_key_exists('steps_to_reproduce', $input)) {
        $stepsInput = $input['steps_to_reproduce'];
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
        $record['steps_to_reproduce'] = $steps;
    }

    if (array_key_exists('affected_versions', $input)) {
        $versionsInput = $input['affected_versions'];
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
        $record['affected_versions'] = $versions;
    }

    if (array_key_exists('environment', $input)) {
        $record['environment'] = trim((string) $input['environment']);
    }

    if (array_key_exists('reference_links', $input)) {
        $referenceInput = $input['reference_links'];
        if (!is_array($referenceInput)) {
            $referenceInput = array_filter(array_map('trim', preg_split('/\r?\n/', (string) $referenceInput) ?: []));
        }
        $references = [];
        foreach ($referenceInput as $link) {
            $normalized = trim((string) $link);
            if ($normalized !== '') {
                $references[] = $normalized;
            }
        }
        $record['reference_links'] = $references;
    }

    if (array_key_exists('attachments', $input)) {
        $attachmentsInput = $input['attachments'];
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
        $record['attachments'] = $attachments;
    }

    if (array_key_exists('resolution_notes', $input)) {
        $record['resolution_notes'] = trim((string) $input['resolution_notes']);
    }

    if (array_key_exists('watchers', $input)) {
        $watchersInput = $input['watchers'];
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
        if (!empty($record['reporter_user_id'])) {
            $watchers[] = (int) $record['reporter_user_id'];
        }
        $record['watchers'] = array_values(array_unique($watchers));
    }

    if (array_key_exists('vote_count', $input)) {
        $voteCount = (int) $input['vote_count'];
        if ($voteCount < 0) {
            $voteCount = 0;
        }
        $record['vote_count'] = $voteCount;
    } else {
        $record['vote_count'] = count($record['watchers'] ?? []);
    }

    $record['updated_at'] = date(DATE_ATOM);
    if (!empty($input['touch_activity'])) {
        $record['last_activity_at'] = $record['updated_at'];
    }

    $dataset['records'][$index] = $record;

    fg_save_bug_reports($dataset, 'Update bug report', [
        'trigger' => $input['trigger'] ?? 'bug_report_updated',
        'performed_by' => $input['performed_by'] ?? null,
        'record_id' => $record['id'] ?? $id,
    ]);

    return $record;
}
