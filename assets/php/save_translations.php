<?php

require_once __DIR__ . '/save_dataset_contents.php';

function fg_save_translations(array $translations, string $reason = 'Translations updated', array $context = []): void
{
    $payload = json_encode($translations);
    if ($payload === false) {
        throw new RuntimeException('Unable to encode translations dataset.');
    }

    fg_save_dataset_contents('translations', $payload, $reason, $context);
}

