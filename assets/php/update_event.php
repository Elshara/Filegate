<?php

require_once __DIR__ . '/load_events.php';
require_once __DIR__ . '/save_events.php';
require_once __DIR__ . '/default_events_dataset.php';
require_once __DIR__ . '/get_setting.php';

function fg_update_event(int $eventId, array $input): array
{
    if ($eventId <= 0) {
        throw new InvalidArgumentException('A valid event identifier is required.');
    }

    try {
        $dataset = fg_load_events();
    } catch (Throwable $exception) {
        $dataset = fg_default_events_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_events_dataset();
    }

    $foundIndex = null;
    foreach ($dataset['records'] as $index => $record) {
        if ((int) ($record['id'] ?? 0) === $eventId) {
            $foundIndex = $index;
            break;
        }
    }

    if ($foundIndex === null) {
        throw new RuntimeException('Unable to locate the requested event.');
    }

    $record = $dataset['records'][$foundIndex];

    $title = trim((string) ($input['title'] ?? $record['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('An event title is required.');
    }

    $summary = trim((string) ($input['summary'] ?? ($record['summary'] ?? '')));
    $description = trim((string) ($input['description'] ?? ($record['description'] ?? '')));

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

    $status = strtolower(trim((string) ($input['status'] ?? ($record['status'] ?? $statusOptions[0]))));
    if (!in_array($status, $statusOptions, true)) {
        $status = $statusOptions[0];
    }

    $visibility = strtolower(trim((string) ($input['visibility'] ?? ($record['visibility'] ?? fg_get_setting('event_default_visibility', 'members')))));
    if (!in_array($visibility, ['public', 'members', 'private'], true)) {
        $visibility = 'members';
    }

    $allowRsvp = array_key_exists('allow_rsvp', $input) ? !empty($input['allow_rsvp']) : !empty($record['allow_rsvp']);

    $rsvpPolicyDefault = strtolower((string) fg_get_setting('event_rsvp_policy', 'members'));
    if (!in_array($rsvpPolicyDefault, ['public', 'members', 'private'], true)) {
        $rsvpPolicyDefault = 'members';
    }
    $existingPolicy = strtolower((string) ($record['rsvp_policy'] ?? $rsvpPolicyDefault));
    $rsvpPolicy = strtolower(trim((string) ($input['rsvp_policy'] ?? $existingPolicy)));
    if (!in_array($rsvpPolicy, ['public', 'members', 'private'], true)) {
        $rsvpPolicy = $existingPolicy;
    }
    if (!$allowRsvp) {
        $rsvpPolicy = 'private';
    }

    $timezone = trim((string) ($input['timezone'] ?? ($record['timezone'] ?? fg_get_setting('event_default_timezone', 'UTC'))));
    if ($timezone === '') {
        $timezone = 'UTC';
    }

    $parseDate = static function (?string $value, ?string $fallback = null) {
        $value = trim((string) $value);
        if ($value === '') {
            $value = $fallback ?? '';
        }
        if ($value === '') {
            return false;
        }
        return strtotime($value);
    };

    $startTimestamp = $parseDate($input['start_at'] ?? null, $record['start_at'] ?? null);
    if ($startTimestamp === false) {
        $startTimestamp = time();
    }

    $endTimestamp = $parseDate($input['end_at'] ?? null, $record['end_at'] ?? null);
    if ($endTimestamp === false || $endTimestamp < $startTimestamp) {
        $endTimestamp = $startTimestamp + 3600;
    }

    $location = trim((string) ($input['location'] ?? ($record['location'] ?? '')));
    $locationUrl = trim((string) ($input['location_url'] ?? ($record['location_url'] ?? '')));

    $rsvpLimit = $record['rsvp_limit'] ?? null;
    if ($allowRsvp) {
        if (array_key_exists('rsvp_limit', $input)) {
            $limitValue = $input['rsvp_limit'];
            if ($limitValue === '' || $limitValue === null) {
                $rsvpLimit = null;
            } else {
                $limitValue = (int) $limitValue;
                $rsvpLimit = $limitValue > 0 ? $limitValue : null;
            }
        }
    } else {
        $rsvpLimit = null;
    }

    $parseIdList = static function ($value) {
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

    $hosts = array_key_exists('hosts', $input) ? $parseIdList($input['hosts']) : ($record['hosts'] ?? []);
    $collaborators = array_key_exists('collaborators', $input) ? $parseIdList($input['collaborators']) : ($record['collaborators'] ?? []);

    $parseStringList = static function ($value, array $fallback = []) {
        if ($value === null) {
            return $fallback;
        }
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

    $tags = array_key_exists('tags', $input) ? $parseStringList($input['tags'], $record['tags'] ?? []) : ($record['tags'] ?? []);
    $attachments = array_key_exists('attachments', $input)
        ? $parseStringList($input['attachments'], $record['attachments'] ?? [])
        : ($record['attachments'] ?? []);

    $now = date(DATE_ATOM);

    $record['title'] = $title;
    $record['summary'] = $summary;
    $record['description'] = $description;
    $record['status'] = $status;
    $record['visibility'] = $visibility;
    $record['start_at'] = date(DATE_ATOM, $startTimestamp);
    $record['end_at'] = date(DATE_ATOM, $endTimestamp);
    $record['timezone'] = $timezone;
    $record['location'] = $location;
    $record['location_url'] = $locationUrl;
    $record['allow_rsvp'] = $allowRsvp;
    $record['rsvp_policy'] = $rsvpPolicy;
    $record['rsvp_limit'] = $rsvpLimit;
    if (!isset($record['rsvps']) || !is_array($record['rsvps'])) {
        $record['rsvps'] = [];
    }
    $record['hosts'] = $hosts;
    $record['collaborators'] = $collaborators;
    $record['tags'] = $tags;
    $record['attachments'] = $attachments;
    $record['updated_at'] = $now;
    $record['last_activity_at'] = $now;
    $record['updated_by'] = isset($input['performed_by']) ? (int) $input['performed_by'] : ($record['updated_by'] ?? null);

    $dataset['records'][$foundIndex] = $record;

    $context = [
        'trigger' => $input['trigger'] ?? 'event_update',
        'record_id' => $record['id'],
    ];
    if (isset($input['performed_by'])) {
        $context['performed_by'] = $input['performed_by'];
    }

    fg_save_events($dataset, 'Update event', $context);

    return $record;
}
