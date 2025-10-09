<?php

require_once __DIR__ . '/load_automations.php';
require_once __DIR__ . '/save_automations.php';
require_once __DIR__ . '/default_automations_dataset.php';

function fg_delete_automation(int $automationId, array $context = []): void
{
    if ($automationId <= 0) {
        throw new InvalidArgumentException('A valid automation identifier is required.');
    }

    try {
        $dataset = fg_load_automations();
    } catch (Throwable $exception) {
        $dataset = fg_default_automations_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_automations_dataset();
    }

    $removed = false;
    $records = [];
    foreach ($dataset['records'] as $record) {
        if ((int) ($record['id'] ?? 0) === $automationId) {
            $removed = true;
            continue;
        }
        $records[] = $record;
    }

    if (!$removed) {
        throw new InvalidArgumentException('The requested automation could not be found.');
    }

    $dataset['records'] = $records;

    $saveContext = $context;
    $saveContext['record_id'] = $automationId;
    $saveContext['trigger'] = $saveContext['trigger'] ?? 'automation_deleted';

    fg_save_automations($dataset, 'Delete automation', $saveContext);
}

