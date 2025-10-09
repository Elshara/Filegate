<?php

require_once __DIR__ . '/dataset_path.php';
require_once __DIR__ . '/dataset_format.php';

function fg_load_notification_templates(): array
{
    if (fg_dataset_format('notification_templates') !== 'xml') {
        return [];
    }

    $path = fg_dataset_path('notification_templates');
    if (!file_exists($path)) {
        return [];
    }

    $xml = simplexml_load_file($path, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xml === false) {
        return [];
    }

    $templates = [];
    foreach ($xml->template as $template) {
        $name = (string) ($template['name'] ?? '');
        if ($name === '') {
            continue;
        }
        $templates[$name] = [
            'channel' => (string) ($template['channel'] ?? ''),
            'description' => (string) ($template->description ?? ''),
            'subject' => (string) ($template->subject ?? ''),
            'body' => (string) ($template->body ?? ''),
        ];
    }

    return $templates;
}

