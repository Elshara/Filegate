<?php

require_once __DIR__ . '/ensure_data_directory.php';
require_once __DIR__ . '/dataset_path.php';
require_once __DIR__ . '/dataset_format.php';
require_once __DIR__ . '/load_dataset_contents.php';
require_once __DIR__ . '/record_dataset_snapshot.php';
require_once __DIR__ . '/record_activity_event.php';

function fg_save_dataset_contents(string $name, string $contents, ?string $reason = null, array $context = []): void
{
    fg_ensure_data_directory();
    $path = fg_dataset_path($name);
    $format = fg_dataset_format($name);

    $previousPayload = null;
    $snapshotRecorded = false;
    $shouldSnapshot = !in_array($name, ['asset_snapshots', 'activity_log'], true);

    if ($shouldSnapshot && file_exists($path)) {
        $previousPayload = fg_load_dataset_contents($name);
        $snapshotContext = $context;
        if (!isset($snapshotContext['trigger'])) {
            $snapshotContext['trigger'] = 'system';
        }
        $snapshotRecorded = fg_record_dataset_snapshot(
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

    $previousHash = null;
    if ($previousPayload !== null) {
        $previousHash = sha1((string) $previousPayload);
    }

    $currentHash = sha1($payload);

    if (file_put_contents($path, $payload) === false) {
        throw new RuntimeException('Unable to write dataset file: ' . $name);
    }

    if ($name !== 'activity_log') {
        $activityContext = $context;
        if (!is_array($activityContext)) {
            $activityContext = [];
        }
        if (!isset($activityContext['dataset'])) {
            $activityContext['dataset'] = $name;
        }
        if (!isset($activityContext['trigger'])) {
            $activityContext['trigger'] = 'dataset_save';
        }

        $activityDetails = [
            'dataset' => $name,
            'format' => $format,
            'reason' => $reason ?? 'Dataset save',
            'trigger' => $activityContext['trigger'],
            'snapshot_recorded' => $snapshotRecorded,
            'had_previous_payload' => $previousPayload !== null,
            'changed' => $previousPayload === null ? true : ((string) $previousPayload !== $payload),
            'bytes_written' => strlen($payload),
            'path' => $path,
            'previous_hash' => $previousHash,
            'current_hash' => $currentHash,
        ];

        fg_record_activity_event('dataset', 'save', $activityDetails, $activityContext);
    }
}

