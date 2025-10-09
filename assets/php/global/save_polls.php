<?php

require_once __DIR__ . '/save_json.php';

function fg_save_polls(array $dataset, ?string $reason = null, array $context = []): void
{
    fg_save_json('polls', $dataset, $reason ?? 'Save poll dataset', $context);
}
