<?php

require_once __DIR__ . '/ensure_data_directory.php';
require_once __DIR__ . '/json_path.php';
require_once __DIR__ . '/save_dataset_contents.php';

function fg_save_json(string $name, array $data, ?string $reason = null, array $context = []): void
{
    fg_ensure_data_directory();
    $path = fg_json_path($name);
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new RuntimeException('Unable to encode data for: ' . $name);
    }

    $saveContext = $context;
    if (!isset($saveContext['trigger'])) {
        $saveContext['trigger'] = 'automation';
    }

    fg_save_dataset_contents(
        $name,
        $encoded,
        $reason ?? ('Programmatic save: ' . $name),
        $saveContext
    );
}

