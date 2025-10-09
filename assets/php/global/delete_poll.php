<?php

require_once __DIR__ . '/load_polls.php';
require_once __DIR__ . '/save_polls.php';
require_once __DIR__ . '/default_polls_dataset.php';

function fg_delete_poll(int $pollId, array $context = []): void
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

    $removed = false;
    $records = [];
    foreach ($dataset['records'] as $record) {
        if ((int) ($record['id'] ?? 0) === $pollId) {
            $removed = true;
            continue;
        }
        $records[] = $record;
    }

    if (!$removed) {
        throw new InvalidArgumentException('The requested poll could not be found.');
    }

    $dataset['records'] = $records;

    $saveContext = array_merge($context, [
        'record_id' => $pollId,
        'trigger' => $context['trigger'] ?? 'poll_delete',
    ]);

    fg_save_polls($dataset, 'Delete poll', $saveContext);
}
