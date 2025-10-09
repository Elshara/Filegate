<?php

require_once __DIR__ . '/load_polls.php';
require_once __DIR__ . '/save_polls.php';
require_once __DIR__ . '/default_polls_dataset.php';
require_once __DIR__ . '/get_setting.php';

function fg_add_poll(array $input): array
{
    $question = trim((string) ($input['question'] ?? ''));
    if ($question === '') {
        throw new InvalidArgumentException('A poll question is required.');
    }

    $description = trim((string) ($input['description'] ?? ''));

    $statusOptions = fg_get_setting('poll_statuses', ['draft', 'open', 'closed']);
    if (!is_array($statusOptions) || empty($statusOptions)) {
        $statusOptions = ['draft', 'open', 'closed'];
    }
    $statusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $statusOptions)));
    if (empty($statusOptions)) {
        $statusOptions = ['draft', 'open', 'closed'];
    }

    $status = strtolower(trim((string) ($input['status'] ?? $statusOptions[0] ?? 'draft')));
    if (!in_array($status, $statusOptions, true)) {
        $status = $statusOptions[0] ?? 'draft';
    }

    $visibilityDefault = strtolower((string) fg_get_setting('poll_default_visibility', 'members'));
    if (!in_array($visibilityDefault, ['public', 'members', 'private'], true)) {
        $visibilityDefault = 'members';
    }
    $visibility = strtolower(trim((string) ($input['visibility'] ?? $visibilityDefault)));
    if (!in_array($visibility, ['public', 'members', 'private'], true)) {
        $visibility = $visibilityDefault;
    }

    $allowMultiple = !empty($input['allow_multiple']);
    $maxSelections = (int) ($input['max_selections'] ?? 0);
    if ($maxSelections < 0) {
        $maxSelections = 0;
    }
    if (!$allowMultiple) {
        $maxSelections = 1;
    } elseif ($maxSelections === 0) {
        $maxSelections = 0; // 0 represents unlimited when multiple selections are enabled.
    }

    $optionsInput = $input['options'] ?? [];
    if (!is_array($optionsInput)) {
        $optionsInput = preg_split('/\r?\n/', (string) $optionsInput) ?: [];
    }

    $optionLabels = [];
    foreach ($optionsInput as $option) {
        if (is_array($option)) {
            $label = trim((string) ($option['label'] ?? ''));
        } else {
            $label = trim((string) $option);
        }
        if ($label === '') {
            continue;
        }
        $key = strtolower($label);
        if (!isset($optionLabels[$key])) {
            $optionLabels[$key] = $label;
        }
    }

    if (count($optionLabels) < 2) {
        throw new InvalidArgumentException('At least two poll options are required.');
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

    $expiresAt = null;
    $expiresAtInput = trim((string) ($input['expires_at'] ?? ''));
    if ($expiresAtInput !== '') {
        $timestamp = strtotime($expiresAtInput);
        if ($timestamp !== false) {
            $expiresAt = date(DATE_ATOM, $timestamp);
        }
    }

    try {
        $dataset = fg_load_polls();
    } catch (Throwable $exception) {
        $dataset = fg_default_polls_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_polls_dataset();
    }

    $nextId = (int) ($dataset['next_id'] ?? 1);
    if ($nextId < 1) {
        $nextId = 1;
    }

    $options = [];
    $optionId = 1;
    foreach ($optionLabels as $label) {
        $options[] = [
            'id' => $optionId,
            'label' => $label,
            'supporters' => [],
            'vote_count' => 0,
        ];
        $optionId++;
    }

    $now = date(DATE_ATOM);
    $publishedAt = null;
    $closedAt = null;
    if ($status !== 'draft') {
        $publishedAt = $now;
    }
    if ($status === 'closed') {
        $closedAt = $now;
    }

    $record = [
        'id' => $nextId,
        'question' => $question,
        'description' => $description,
        'status' => $status,
        'visibility' => $visibility,
        'allow_multiple' => $allowMultiple,
        'max_selections' => $maxSelections,
        'owner_role' => $ownerRole,
        'owner_user_id' => $ownerUserId,
        'options' => $options,
        'total_votes' => 0,
        'total_responses' => 0,
        'created_at' => $now,
        'updated_at' => $now,
        'last_activity_at' => $now,
        'published_at' => $publishedAt,
        'closed_at' => $closedAt,
        'expires_at' => $expiresAt,
        'next_option_id' => $optionId,
    ];

    $dataset['records'][] = $record;
    $dataset['next_id'] = $nextId + 1;

    $context = [
        'trigger' => $input['trigger'] ?? 'poll_creation',
        'record_id' => $record['id'],
    ];
    if (isset($input['performed_by'])) {
        $context['performed_by'] = $input['performed_by'];
    } elseif ($ownerUserId !== null) {
        $context['performed_by'] = $ownerUserId;
    }

    fg_save_polls($dataset, 'Create poll', $context);

    return $record;
}
