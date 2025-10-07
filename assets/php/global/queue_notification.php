<?php

require_once __DIR__ . '/load_notifications.php';
require_once __DIR__ . '/save_notifications.php';
require_once __DIR__ . '/load_notification_channels.php';
require_once __DIR__ . '/load_notification_templates.php';
require_once __DIR__ . '/get_setting.php';

function fg_queue_notification(array $payload, ?array $channels = null): array
{
    $definitions = fg_load_notification_channels();
    $templates = fg_load_notification_templates();
    $configured = fg_get_setting('notification_channel_defaults', ['email', 'browser']);
    if (is_string($configured)) {
        $decoded = json_decode($configured, true);
        if (is_array($decoded)) {
            $configured = $decoded;
        } else {
            $configured = array_filter(array_map('trim', explode(',', $configured)));
        }
    }

    $channels = $channels ?? $configured;
    if (!is_array($channels)) {
        $channels = [$channels];
    }

    $channels = array_values(array_unique(array_filter(array_map(static function ($channel) {
        return is_string($channel) ? strtolower(trim($channel)) : null;
    }, $channels))));

    if ($channels === []) {
        return [];
    }

    $notifications = fg_load_notifications();
    $queued = [];

    foreach ($channels as $channel) {
        if (!isset($definitions[$channel])) {
            continue;
        }

        $template_name = $payload['template'] ?? ($definitions[$channel]['default_template'] ?? null);
        $template = $template_name && isset($templates[$template_name]) ? $templates[$template_name] : null;

        $id = $notifications['next_id'] ?? 1;
        $notifications['next_id'] = $id + 1;

        $record = [
            'id' => $id,
            'channel' => $channel,
            'payload' => $payload,
            'template' => $template_name,
            'composed_subject' => $template ? strtr($template['subject'], $payload['variables'] ?? []) : ($payload['subject'] ?? ''),
            'composed_body' => $template ? strtr($template['body'], $payload['variables'] ?? []) : ($payload['body'] ?? ''),
            'created_at' => date(DATE_ATOM),
            'status' => 'pending',
        ];

        $notifications['records'][] = $record;
        $queued[] = $record;
    }

    fg_save_notifications($notifications);

    return $queued;
}

