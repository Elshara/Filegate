<?php

require_once __DIR__ . '/load_feature_requests.php';
require_once __DIR__ . '/save_feature_requests.php';
require_once __DIR__ . '/default_feature_requests_dataset.php';
require_once __DIR__ . '/get_setting.php';

function fg_add_feature_request(array $input): array
{
    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('A title is required when submitting a feature request.');
    }

    $summary = trim((string) ($input['summary'] ?? ''));
    $details = trim((string) ($input['details'] ?? ''));

    $statusOptions = fg_get_setting('feature_request_statuses', ['open', 'researching', 'planned', 'in_progress', 'completed', 'declined']);
    if (!is_array($statusOptions) || empty($statusOptions)) {
        $statusOptions = ['open'];
    }
    $statusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $statusOptions)));
    if (empty($statusOptions)) {
        $statusOptions = ['open'];
    }

    $status = strtolower(trim((string) ($input['status'] ?? $statusOptions[0])));
    if (!in_array($status, $statusOptions, true)) {
        $status = $statusOptions[0];
    }

    $priorityOptions = fg_get_setting('feature_request_priorities', ['low', 'medium', 'high', 'critical']);
    if (!is_array($priorityOptions) || empty($priorityOptions)) {
        $priorityOptions = ['medium'];
    }
    $priorityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $priorityOptions)));
    if (empty($priorityOptions)) {
        $priorityOptions = ['medium'];
    }

    $priority = strtolower(trim((string) ($input['priority'] ?? $priorityOptions[0])));
    if (!in_array($priority, $priorityOptions, true)) {
        $priority = $priorityOptions[0];
    }

    $visibilityDefault = fg_get_setting('feature_request_default_visibility', 'members');
    $visibility = strtolower(trim((string) ($input['visibility'] ?? $visibilityDefault)));
    $visibilityOptions = ['public', 'members', 'private'];
    if (!in_array($visibility, $visibilityOptions, true)) {
        $visibility = in_array($visibilityDefault, $visibilityOptions, true) ? $visibilityDefault : 'members';
    }

    $impact = (int) ($input['impact'] ?? 3);
    if ($impact < 1) {
        $impact = 1;
    }
    if ($impact > 5) {
        $impact = 5;
    }

    $effort = (int) ($input['effort'] ?? 3);
    if ($effort < 1) {
        $effort = 1;
    }
    if ($effort > 5) {
        $effort = 5;
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

    $linksInput = $input['reference_links'] ?? [];
    if (!is_array($linksInput)) {
        $linksInput = preg_split('/\r?\n/', (string) $linksInput) ?: [];
    }
    $referenceLinks = [];
    foreach ($linksInput as $link) {
        $normalized = trim((string) $link);
        if ($normalized !== '') {
            $referenceLinks[] = $normalized;
        }
    }

    $requestorUserId = $input['requestor_user_id'] ?? null;
    if ($requestorUserId !== null) {
        $requestorUserId = (int) $requestorUserId;
        if ($requestorUserId <= 0) {
            $requestorUserId = null;
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

    $supportersInput = $input['supporters'] ?? [];
    if (!is_array($supportersInput)) {
        $supportersInput = preg_split('/[,\n]+/', (string) $supportersInput) ?: [];
    }
    $supporters = [];
    foreach ($supportersInput as $supporter) {
        $id = (int) trim((string) $supporter);
        if ($id > 0 && !in_array($id, $supporters, true)) {
            $supporters[] = $id;
        }
    }

    try {
        $dataset = fg_load_feature_requests();
    } catch (Throwable $exception) {
        $dataset = fg_default_feature_requests_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_feature_requests_dataset();
    }

    $nextId = (int) ($dataset['next_id'] ?? 1);
    if ($nextId < 1) {
        $nextId = 1;
    }

    $now = date(DATE_ATOM);
    $record = [
        'id' => $nextId,
        'title' => $title,
        'summary' => $summary,
        'details' => $details,
        'status' => $status,
        'priority' => $priority,
        'visibility' => $visibility,
        'requestor_user_id' => $requestorUserId,
        'owner_role' => $ownerRole,
        'owner_user_id' => $ownerUserId,
        'tags' => $tags,
        'reference_links' => $referenceLinks,
        'supporters' => $supporters,
        'vote_count' => count($supporters),
        'impact' => $impact,
        'effort' => $effort,
        'admin_notes' => trim((string) ($input['admin_notes'] ?? '')),
        'created_at' => $now,
        'updated_at' => $now,
        'last_activity_at' => $now,
    ];

    $dataset['records'][] = $record;
    $dataset['next_id'] = $nextId + 1;

    $context = [
        'trigger' => $input['trigger'] ?? 'feature_request_submission',
        'record_id' => $record['id'],
    ];
    if (isset($input['performed_by'])) {
        $context['performed_by'] = $input['performed_by'];
    } elseif ($requestorUserId !== null) {
        $context['performed_by'] = $requestorUserId;
    }

    fg_save_feature_requests($dataset, 'Create feature request', $context);

    return $record;
}

