<?php

require_once __DIR__ . '/load_activity_log.php';
require_once __DIR__ . '/save_activity_log.php';
require_once __DIR__ . '/find_user_by_id.php';
require_once __DIR__ . '/current_user.php';
require_once __DIR__ . '/default_activity_log_dataset.php';

function fg_record_activity_event(string $category, string $action, array $details = [], array $context = []): void
{
    $log = fg_load_activity_log();
    if (!isset($log['records']) || !is_array($log['records'])) {
        $log = fg_default_activity_log_dataset();
    }

    if (!is_array($details)) {
        $details = ['value' => $details];
    }

    if (!is_array($context)) {
        $context = [];
    }

    $performedById = $context['performed_by'] ?? $context['restored_by'] ?? $context['created_by'] ?? null;
    if ($performedById === null) {
        $current = fg_current_user();
        if ($current) {
            $performedById = $current['id'] ?? null;
        }
    }

    $performedBy = null;
    if ($performedById !== null) {
        $user = fg_find_user_by_id((int) $performedById);
        if ($user) {
            $performedBy = [
                'id' => (int) ($user['id'] ?? 0),
                'username' => $user['username'] ?? ($user['email'] ?? ''),
                'email' => $user['email'] ?? '',
                'role' => $user['role'] ?? '',
            ];
        } else {
            $performedBy = [
                'id' => (int) $performedById,
            ];
        }
    }

    $records = $log['records'];
    if (!is_array($records)) {
        $records = [];
    }

    $record = [
        'id' => (int) ($log['next_id'] ?? 1),
        'created_at' => gmdate('c'),
        'category' => $category,
        'action' => $action,
        'dataset' => $details['dataset'] ?? ($context['dataset'] ?? null),
        'details' => $details,
        'context' => $context,
        'performed_by' => $performedBy,
        'trigger' => $context['trigger'] ?? ($details['trigger'] ?? 'system'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ];
    array_unshift($records, $record);

    $log['records'] = $records;
    $log['next_id'] = $record['id'] + 1;

    $limit = (int) ($log['metadata']['limit'] ?? 0);
    if ($limit > 0 && count($log['records']) > $limit) {
        $log['records'] = array_slice($log['records'], 0, $limit);
    }

    fg_save_activity_log($log);
}
