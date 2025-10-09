<?php

require_once __DIR__ . '/save_json.php';

function fg_save_events(array $dataset, ?string $reason = null, array $context = []): void
{
    fg_save_json('events', $dataset, $reason ?? 'Save event dataset', $context);
}
