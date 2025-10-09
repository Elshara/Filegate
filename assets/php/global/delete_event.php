<?php

require_once __DIR__ . '/load_events.php';
require_once __DIR__ . '/save_events.php';
require_once __DIR__ . '/default_events_dataset.php';

function fg_delete_event(int $eventId, array $context = []): void
{
    if ($eventId <= 0) {
        throw new InvalidArgumentException('A valid event identifier is required for deletion.');
    }

    try {
        $dataset = fg_load_events();
    } catch (Throwable $exception) {
        $dataset = fg_default_events_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_events_dataset();
    }

    $originalCount = count($dataset['records']);
    $dataset['records'] = array_values(array_filter($dataset['records'], static function ($record) use ($eventId) {
        return (int) ($record['id'] ?? 0) !== $eventId;
    }));

    if ($originalCount === count($dataset['records'])) {
        throw new RuntimeException('The requested event could not be found.');
    }

    $saveContext = $context;
    $saveContext['trigger'] = $saveContext['trigger'] ?? 'event_deletion';
    $saveContext['record_id'] = $eventId;

    fg_save_events($dataset, 'Delete event', $saveContext);
}
