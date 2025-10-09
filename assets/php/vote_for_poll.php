<?php

require_once __DIR__ . '/load_polls.php';
require_once __DIR__ . '/save_polls.php';
require_once __DIR__ . '/default_polls_dataset.php';

function fg_vote_for_poll(int $pollId, int $userId, array $selectionIds, array $context = []): array
{
    if ($pollId <= 0) {
        throw new InvalidArgumentException('A valid poll identifier is required.');
    }
    if ($userId <= 0) {
        throw new InvalidArgumentException('A valid user identifier is required.');
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

    $status = strtolower((string) ($record['status'] ?? 'draft'));
    if ($status !== 'open') {
        throw new RuntimeException('Voting is only available on open polls.');
    }

    $expiresAt = $record['expires_at'] ?? null;
    if ($expiresAt) {
        $timestamp = strtotime((string) $expiresAt);
        if ($timestamp !== false && $timestamp <= time()) {
            throw new RuntimeException('This poll has already closed.');
        }
    }

    $allowMultiple = (bool) ($record['allow_multiple'] ?? false);
    $maxSelections = (int) ($record['max_selections'] ?? ($allowMultiple ? 0 : 1));
    if ($maxSelections < 0) {
        $maxSelections = 0;
    }
    if (!$allowMultiple) {
        $maxSelections = 1;
    }

    $selected = [];
    foreach ($selectionIds as $value) {
        $id = (int) $value;
        if ($id > 0 && !in_array($id, $selected, true)) {
            $selected[] = $id;
        }
    }

    if (!$allowMultiple && count($selected) > 1) {
        $selected = array_slice($selected, 0, 1);
    }
    if ($allowMultiple && $maxSelections > 0 && count($selected) > $maxSelections) {
        $selected = array_slice($selected, 0, $maxSelections);
    }

    $options = [];
    $totalVotes = 0;
    $uniqueSupporters = [];
    foreach ($record['options'] ?? [] as $option) {
        if (!is_array($option)) {
            continue;
        }
        $optionId = (int) ($option['id'] ?? 0);
        $option['supporters'] = array_values(array_unique(array_filter(array_map('intval', $option['supporters'] ?? []), static function ($value) {
            return $value > 0;
        })));
        $option['supporters'] = array_values(array_filter($option['supporters'], static function ($supporterId) use ($userId) {
            return $supporterId !== $userId;
        }));
        if (in_array($optionId, $selected, true)) {
            $option['supporters'][] = $userId;
        }
        $option['supporters'] = array_values(array_unique($option['supporters']));
        $option['vote_count'] = count($option['supporters']);
        $totalVotes += $option['vote_count'];
        foreach ($option['supporters'] as $supporterId) {
            $uniqueSupporters[$supporterId] = true;
        }
        $options[] = $option;
    }

    $record['options'] = $options;
    $record['total_votes'] = $totalVotes;
    $record['total_responses'] = count($uniqueSupporters);
    $record['updated_at'] = date(DATE_ATOM);
    $record['last_activity_at'] = $record['updated_at'];

    $dataset['records'][$index] = $record;

    $saveContext = array_merge($context, [
        'trigger' => $context['trigger'] ?? 'poll_vote',
        'record_id' => $record['id'],
        'performed_by' => $userId,
    ]);

    fg_save_polls($dataset, 'Update poll votes', $saveContext);

    return $record;
}
