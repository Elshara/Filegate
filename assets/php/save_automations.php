<?php

require_once __DIR__ . '/save_json.php';

function fg_save_automations(array $dataset, ?string $reason = null, array $context = []): void
{
    fg_save_json('automations', $dataset, $reason ?? 'Save automation dataset', $context);
}

