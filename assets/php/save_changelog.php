<?php

require_once __DIR__ . '/save_json.php';

function fg_save_changelog(array $dataset, ?string $reason = null, array $context = []): void
{
    fg_save_json('changelog', $dataset, $reason, $context);
}

