<?php

require_once __DIR__ . '/ensure_data_directory.php';
require_once __DIR__ . '/dataset_path.php';
require_once __DIR__ . '/dataset_format.php';
require_once __DIR__ . '/load_dataset_contents.php';
require_once __DIR__ . '/record_dataset_snapshot.php';

function fg_save_dataset_contents(string $name, string $contents, ?string $reason = null, array $context = []): void
{
    fg_ensure_data_directory();
    $path = fg_dataset_path($name);
    $format = fg_dataset_format($name);

    $previousPayload = null;
    if ($name !== 'asset_snapshots' && file_exists($path)) {
        $previousPayload = fg_load_dataset_contents($name);
        $snapshotContext = $context;
        if (!isset($snapshotContext['trigger'])) {
            $snapshotContext['trigger'] = 'system';
        }
        fg_record_dataset_snapshot(
            $name,
            $reason ?? 'Dataset save',
            $snapshotContext,
            $previousPayload
        );
    }

    if ($format === 'json') {
        $decoded = json_decode($contents, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Provided JSON is invalid for dataset: ' . $name);
        }
        $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException('Unable to encode JSON for dataset: ' . $name);
        }
        $payload = $encoded . "\n";
    } elseif ($format === 'xml') {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        if (@$document->loadXML($contents) === false) {
            throw new InvalidArgumentException('Provided XML is invalid for dataset: ' . $name);
        }
        $payload = $document->saveXML();
        if ($payload === false) {
            throw new RuntimeException('Unable to encode XML for dataset: ' . $name);
        }
    } else {
        $payload = $contents;
    }

    if (file_put_contents($path, $payload) === false) {
        throw new RuntimeException('Unable to write dataset file: ' . $name);
    }
}

