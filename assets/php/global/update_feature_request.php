<?php

require_once __DIR__ . '/load_feature_requests.php';
require_once __DIR__ . '/save_feature_requests.php';
require_once __DIR__ . '/default_feature_requests_dataset.php';
require_once __DIR__ . '/get_setting.php';

function fg_update_feature_request(int $id, array $input): array
{
    if ($id <= 0) {
        throw new InvalidArgumentException('A valid feature request identifier is required.');
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
        throw new RuntimeException('Unable to locate the requested feature entry.');
    }

    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('A title is required when updating a feature request.');
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
    $status = strtolower(trim((string) ($input['status'] ?? $dataset['records'][$index]['status'] ?? $statusOptions[0])));
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
    $priority = strtolower(trim((string) ($input['priority'] ?? $dataset['records'][$index]['priority'] ?? $priorityOptions[0])));
    if (!in_array($priority, $priorityOptions, true)) {
        $priority = $priorityOptions[0];
    }

    $visibilityDefault = fg_get_setting('feature_request_default_visibility', 'members');
    $visibility = strtolower(trim((string) ($input['visibility'] ?? $dataset['records'][$index]['visibility'] ?? $visibilityDefault)));
    $visibilityOptions = ['public', 'members', 'private'];
    if (!in_array($visibility, $visibilityOptions, true)) {
        $visibility = in_array($visibilityDefault, $visibilityOptions, true) ? $visibilityDefault : 'members';
    }

    $impact = (int) ($input['impact'] ?? $dataset['records'][$index]['impact'] ?? 3);
    if ($impact < 1) {
        $impact = 1;
    }
    if ($impact > 5) {
        $impact = 5;
    }

    $effort = (int) ($input['effort'] ?? $dataset['records'][$index]['effort'] ?? 3);
    if ($effort < 1) {
        $effort = 1;
    }
    if ($effort > 5) {
        $effort = 5;
    }

    $tagsInput = $input['tags'] ?? $dataset['records'][$index]['tags'] ?? [];
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

    $linksInput = $input['reference_links'] ?? $dataset['records'][$index]['reference_links'] ?? [];
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

    $requestorUserId = $dataset['records'][$index]['requestor_user_id'] ?? null;
    if (array_key_exists('requestor_user_id', $input)) {
        $candidate = $input['requestor_user_id'];
        if ($candidate === null || $candidate === '') {
            $requestorUserId = null;
        } else {
            $requestorUserId = (int) $candidate;
            if ($requestorUserId <= 0) {
                $requestorUserId = null;
            }
        }
    }

    $ownerRole = $dataset['records'][$index]['owner_role'] ?? null;
    if (array_key_exists('owner_role', $input)) {
        $ownerRole = trim((string) $input['owner_role']);
        if ($ownerRole === '') {
            $ownerRole = null;
        }
    }

    $ownerUserId = $dataset['records'][$index]['owner_user_id'] ?? null;
    if (array_key_exists('owner_user_id', $input)) {
        $candidate = $input['owner_user_id'];
        if ($candidate === null || $candidate === '') {
            $ownerUserId = null;
        } else {
            $ownerUserId = (int) $candidate;
            if ($ownerUserId <= 0) {
                $ownerUserId = null;
            }
        }
    }

    $supportersInput = $input['supporters'] ?? $dataset['records'][$index]['supporters'] ?? [];
    if (!is_array($supportersInput)) {
        $supportersInput = preg_split('/[,\n]+/', (string) $supportersInput) ?: [];
    }
    $supporters = [];
    foreach ($supportersInput as $supporter) {
        $idValue = trim((string) $supporter);
        if ($idValue === '') {
            continue;
        }
        $id = (int) $idValue;
        if ($id > 0 && !in_array($id, $supporters, true)) {
            $supporters[] = $id;
        }
    }

    $record = $dataset['records'][$index];
    $record['title'] = $title;
    $record['summary'] = $summary;
    $record['details'] = $details;
    $record['status'] = $status;
    $record['priority'] = $priority;
    $record['visibility'] = $visibility;
    $record['impact'] = $impact;
    $record['effort'] = $effort;
    $record['tags'] = $tags;
    $record['reference_links'] = $referenceLinks;
    $record['requestor_user_id'] = $requestorUserId;
    $record['owner_role'] = $ownerRole;
    $record['owner_user_id'] = $ownerUserId;
    $record['supporters'] = $supporters;
    $record['vote_count'] = count($supporters);
    $record['admin_notes'] = trim((string) ($input['admin_notes'] ?? $record['admin_notes'] ?? ''));
    $record['updated_at'] = date(DATE_ATOM);
    $record['last_activity_at'] = $record['updated_at'];

    $dataset['records'][$index] = $record;

    $context = [
        'trigger' => $input['trigger'] ?? 'feature_request_update',
        'record_id' => $id,
    ];
    if (isset($input['performed_by'])) {
        $context['performed_by'] = $input['performed_by'];
    }

    fg_save_feature_requests($dataset, 'Update feature request', $context);

    return $record;
}

