<?php

require_once __DIR__ . '/load_events.php';
require_once __DIR__ . '/save_events.php';
require_once __DIR__ . '/default_events_dataset.php';
require_once __DIR__ . '/get_setting.php';

function fg_add_event(array $input): array
{
    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('An event title is required.');
    }

    $summary = trim((string) ($input['summary'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));

    $statusOptions = fg_get_setting('event_statuses', ['draft', 'scheduled', 'completed', 'cancelled']);
    if (!is_array($statusOptions) || empty($statusOptions)) {
        $statusOptions = ['draft', 'scheduled', 'completed', 'cancelled'];
    }
    $statusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $statusOptions)));
    if (empty($statusOptions)) {
        $statusOptions = ['draft'];
    }

    $status = strtolower(trim((string) ($input['status'] ?? $statusOptions[0])));
    if (!in_array($status, $statusOptions, true)) {
        $status = $statusOptions[0];
    }

    $visibility = strtolower(trim((string) ($input['visibility'] ?? fg_get_setting('event_default_visibility', 'members'))));
    if (!in_array($visibility, ['public', 'members', 'private'], true)) {
        $visibility = 'members';
    }

    $allowRsvp = !empty($input['allow_rsvp']);
    $rsvpPolicyDefault = strtolower((string) fg_get_setting('event_rsvp_policy', 'members'));
    if (!in_array($rsvpPolicyDefault, ['public', 'members', 'private'], true)) {
        $rsvpPolicyDefault = 'members';
    }
    $rsvpPolicy = strtolower(trim((string) ($input['rsvp_policy'] ?? $rsvpPolicyDefault)));
    if (!in_array($rsvpPolicy, ['public', 'members', 'private'], true)) {
        $rsvpPolicy = $rsvpPolicyDefault;
    }
    if (!$allowRsvp) {
        $rsvpPolicy = 'private';
    }

    $timezone = trim((string) ($input['timezone'] ?? fg_get_setting('event_default_timezone', 'UTC')));
    if ($timezone === '') {
        $timezone = 'UTC';
    }

    $startInput = trim((string) ($input['start_at'] ?? ''));
    $endInput = trim((string) ($input['end_at'] ?? ''));
    $startTimestamp = $startInput !== '' ? strtotime($startInput) : false;
    if ($startTimestamp === false) {
        $startTimestamp = time();
    }
    $endTimestamp = $endInput !== '' ? strtotime($endInput) : false;
    if ($endTimestamp === false || $endTimestamp < $startTimestamp) {
        $endTimestamp = $startTimestamp + 3600;
    }

    $location = trim((string) ($input['location'] ?? ''));
    $locationUrl = trim((string) ($input['location_url'] ?? ''));

    $rsvpLimit = null;
    if ($allowRsvp) {
        $rsvpLimitValue = $input['rsvp_limit'] ?? null;
        if ($rsvpLimitValue !== null && $rsvpLimitValue !== '') {
            $rsvpLimitValue = (int) $rsvpLimitValue;
            if ($rsvpLimitValue > 0) {
                $rsvpLimit = $rsvpLimitValue;
            }
        }
    }

    $parseIdList = static function ($value): array {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/[\r\n,]+/', (string) $value) ?: [];
        }
        $ids = [];
        foreach ($items as $item) {
            $id = (int) trim((string) $item);
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return $ids;
    };

    $hosts = $parseIdList($input['hosts'] ?? []);
    if (empty($hosts) && isset($input['performed_by'])) {
        $performedBy = (int) $input['performed_by'];
        if ($performedBy > 0) {
            $hosts[] = $performedBy;
        }
    }

    $collaborators = $parseIdList($input['collaborators'] ?? []);

    $parseStringList = static function ($value): array {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/[\r\n,]+/', (string) $value) ?: [];
        }
        $result = [];
        foreach ($items as $item) {
            $label = trim((string) $item);
            if ($label === '') {
                continue;
            }
            $key = strtolower($label);
            if (!isset($result[$key])) {
                $result[$key] = $label;
            }
        }
        return array_values($result);
    };

    $tags = $parseStringList($input['tags'] ?? []);
    $attachments = $parseStringList($input['attachments'] ?? []);

    try {
        $dataset = fg_load_events();
    } catch (Throwable $exception) {
        $dataset = fg_default_events_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_events_dataset();
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
        'description' => $description,
        'status' => $status,
        'visibility' => $visibility,
        'start_at' => date(DATE_ATOM, $startTimestamp),
        'end_at' => date(DATE_ATOM, $endTimestamp),
        'timezone' => $timezone,
        'location' => $location,
        'location_url' => $locationUrl,
        'allow_rsvp' => $allowRsvp,
        'rsvp_policy' => $rsvpPolicy,
        'rsvp_limit' => $rsvpLimit,
        'rsvps' => [],
        'hosts' => $hosts,
        'collaborators' => $collaborators,
        'tags' => $tags,
        'attachments' => $attachments,
        'created_at' => $now,
        'updated_at' => $now,
        'last_activity_at' => $now,
        'created_by' => isset($input['performed_by']) ? (int) $input['performed_by'] : null,
        'updated_by' => isset($input['performed_by']) ? (int) $input['performed_by'] : null,
    ];

    $dataset['records'][] = $record;
    $dataset['next_id'] = $nextId + 1;

    $context = [
        'trigger' => $input['trigger'] ?? 'event_creation',
        'record_id' => $record['id'],
    ];
    if (isset($input['performed_by'])) {
        $context['performed_by'] = $input['performed_by'];
    }

    fg_save_events($dataset, 'Create event', $context);

    return $record;
}
