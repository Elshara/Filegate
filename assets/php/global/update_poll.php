<?php

require_once __DIR__ . '/load_polls.php';
require_once __DIR__ . '/save_polls.php';
require_once __DIR__ . '/default_polls_dataset.php';
require_once __DIR__ . '/get_setting.php';

function fg_update_poll(int $pollId, array $input): array
{
    if ($pollId <= 0) {
        throw new InvalidArgumentException('A valid poll identifier is required.');
    }

    try {
        $dataset = fg_load_polls();
    } catch (Throwable $exception) {
        $dataset = fg_default_polls_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_polls_dataset();
    }

    $index = null;
    foreach ($dataset['records'] as $key => $record) {
        if ((int) ($record['id'] ?? 0) === $pollId) {
            $index = $key;
            break;
        }
    }

    if ($index === null) {
        throw new InvalidArgumentException('The requested poll could not be found.');
    }

    $record = $dataset['records'][$index];

    $question = trim((string) ($input['question'] ?? $record['question'] ?? ''));
    if ($question === '') {
        throw new InvalidArgumentException('A poll question is required.');
    }

    $description = trim((string) ($input['description'] ?? $record['description'] ?? ''));

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

    $newStatus = strtolower(trim((string) ($input['status'] ?? $record['status'] ?? $statusOptions[0])));
    if (!in_array($newStatus, $statusOptions, true)) {
        $newStatus = $record['status'] ?? $statusOptions[0];
    }

    $visibilityDefault = strtolower((string) fg_get_setting('poll_default_visibility', 'members'));
    if (!in_array($visibilityDefault, ['public', 'members', 'private'], true)) {
        $visibilityDefault = 'members';
    }
    $newVisibility = strtolower(trim((string) ($input['visibility'] ?? $record['visibility'] ?? $visibilityDefault)));
    if (!in_array($newVisibility, ['public', 'members', 'private'], true)) {
        $newVisibility = $record['visibility'] ?? $visibilityDefault;
    }

    $allowMultiple = array_key_exists('allow_multiple', $input)
        ? !empty($input['allow_multiple'])
        : (bool) ($record['allow_multiple'] ?? false);

    $maxSelections = (int) ($input['max_selections'] ?? ($record['max_selections'] ?? ($allowMultiple ? 0 : 1)));
    if ($maxSelections < 0) {
        $maxSelections = 0;
    }
    if (!$allowMultiple) {
        $maxSelections = 1;
    } elseif ($maxSelections === 0) {
        $maxSelections = 0;
    }

    $optionsInput = $input['options'] ?? null;
    if ($optionsInput === null) {
        $optionsInput = [];
        foreach ($record['options'] ?? [] as $existingOption) {
            $optionsInput[] = $existingOption['label'] ?? '';
        }
    }
    if (!is_array($optionsInput)) {
        $optionsInput = preg_split('/\r?\n/', (string) $optionsInput) ?: [];
    }

    $existingOptions = [];
    $existingByLabel = [];
    $highestOptionId = 0;
    foreach ($record['options'] ?? [] as $option) {
        if (!is_array($option)) {
            continue;
        }
        $optionId = (int) ($option['id'] ?? 0);
        if ($optionId > $highestOptionId) {
            $highestOptionId = $optionId;
        }
        $label = trim((string) ($option['label'] ?? ''));
        $normalized = strtolower($label);
        $option['label'] = $label;
        if (!isset($option['supporters']) || !is_array($option['supporters'])) {
            $option['supporters'] = [];
        }
        $option['supporters'] = array_values(array_unique(array_filter(array_map('intval', $option['supporters']), static function ($value) {
            return $value > 0;
        })));
        $option['vote_count'] = count($option['supporters']);
        $existingOptions[$optionId] = $option;
        if ($label !== '') {
            $existingByLabel[$normalized] = $option;
        }
    }

    $newOptions = [];
    $seen = [];
    $nextOptionId = (int) ($record['next_option_id'] ?? ($highestOptionId + 1));
    if ($nextOptionId <= $highestOptionId) {
        $nextOptionId = $highestOptionId + 1;
    }

    foreach ($optionsInput as $optionValue) {
        if (is_array($optionValue)) {
            $label = trim((string) ($optionValue['label'] ?? ''));
        } else {
            $label = trim((string) $optionValue);
        }
        if ($label === '') {
            continue;
        }
        $normalized = strtolower($label);
        if (isset($seen[$normalized])) {
            continue;
        }
        $seen[$normalized] = true;

        if (isset($existingByLabel[$normalized])) {
            $option = $existingByLabel[$normalized];
            $option['label'] = $label;
        } else {
            $option = [
                'id' => $nextOptionId,
                'label' => $label,
                'supporters' => [],
                'vote_count' => 0,
            ];
            $nextOptionId++;
        }
        $newOptions[] = $option;
    }

    if (count($newOptions) < 2) {
        throw new InvalidArgumentException('At least two poll options are required.');
    }

    $expiresAt = null;
    $expiresAtInput = array_key_exists('expires_at', $input)
        ? trim((string) $input['expires_at'])
        : (string) ($record['expires_at'] ?? '');
    if ($expiresAtInput !== '') {
        $timestamp = strtotime($expiresAtInput);
        if ($timestamp !== false) {
            $expiresAt = date(DATE_ATOM, $timestamp);
        }
    }

    $ownerRole = array_key_exists('owner_role', $input)
        ? trim((string) $input['owner_role'])
        : ($record['owner_role'] ?? null);
    if ($ownerRole === '') {
        $ownerRole = null;
    }

    $ownerUserId = array_key_exists('owner_user_id', $input)
        ? $input['owner_user_id']
        : ($record['owner_user_id'] ?? null);
    if ($ownerUserId !== null) {
        $ownerUserId = (int) $ownerUserId;
        if ($ownerUserId <= 0) {
            $ownerUserId = null;
        }
    }

    $totalVotes = 0;
    $uniqueSupporters = [];
    foreach ($newOptions as &$option) {
        $option['supporters'] = array_values(array_unique(array_filter(array_map('intval', $option['supporters']), static function ($value) {
            return $value > 0;
        })));
        $option['vote_count'] = count($option['supporters']);
        $totalVotes += $option['vote_count'];
        foreach ($option['supporters'] as $supporterId) {
            $uniqueSupporters[$supporterId] = true;
        }
    }
    unset($option);

    $now = date(DATE_ATOM);
    $previousStatus = strtolower((string) ($record['status'] ?? 'draft'));
    $publishedAt = $record['published_at'] ?? null;
    $closedAt = $record['closed_at'] ?? null;

    if ($previousStatus === 'draft' && $newStatus !== 'draft' && ($publishedAt === null || $publishedAt === '')) {
        $publishedAt = $now;
    }
    if ($newStatus === 'closed') {
        $closedAt = $closedAt ?: $now;
    } elseif ($previousStatus === 'closed' && $newStatus !== 'closed') {
        $closedAt = null;
    }

    $record['question'] = $question;
    $record['description'] = $description;
    $record['status'] = $newStatus;
    $record['visibility'] = $newVisibility;
    $record['allow_multiple'] = $allowMultiple;
    $record['max_selections'] = $maxSelections;
    $record['options'] = $newOptions;
    $record['total_votes'] = $totalVotes;
    $record['total_responses'] = count($uniqueSupporters);
    $record['updated_at'] = $now;
    $record['last_activity_at'] = $now;
    $record['published_at'] = $publishedAt;
    $record['closed_at'] = $closedAt;
    $record['expires_at'] = $expiresAt;
    $record['owner_role'] = $ownerRole;
    $record['owner_user_id'] = $ownerUserId;
    $record['next_option_id'] = $nextOptionId;

    $dataset['records'][$index] = $record;

    $context = [
        'trigger' => $input['trigger'] ?? 'poll_update',
        'record_id' => $record['id'],
    ];
    if (isset($input['performed_by'])) {
        $context['performed_by'] = $input['performed_by'];
    }

    fg_save_polls($dataset, 'Update poll', $context);

    return $record;
}
