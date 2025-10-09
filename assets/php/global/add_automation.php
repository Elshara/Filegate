<?php

require_once __DIR__ . '/load_automations.php';
require_once __DIR__ . '/save_automations.php';
require_once __DIR__ . '/default_automations_dataset.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/parse_automation_lines.php';

function fg_add_automation(array $input, array $context = []): array
{
    $name = trim((string) ($input['name'] ?? ''));
    if ($name === '') {
        throw new InvalidArgumentException('A name is required when creating an automation.');
    }

    $description = trim((string) ($input['description'] ?? ''));

    $statusOptions = fg_get_setting('automation_statuses', ['enabled', 'paused', 'disabled']);
    if (!is_array($statusOptions) || empty($statusOptions)) {
        $statusOptions = ['enabled', 'paused', 'disabled'];
    }
    $statusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $statusOptions)));
    if (empty($statusOptions)) {
        $statusOptions = ['enabled'];
    }

    $defaultStatus = strtolower((string) fg_get_setting('automation_default_status', $statusOptions[0]));
    if (!in_array($defaultStatus, $statusOptions, true)) {
        $defaultStatus = $statusOptions[0];
    }

    $status = strtolower(trim((string) ($input['status'] ?? $defaultStatus)));
    if (!in_array($status, $statusOptions, true)) {
        $status = $defaultStatus;
    }

    $triggerOptions = fg_get_setting('automation_triggers', ['user_registered', 'post_published', 'feature_request_submitted', 'bug_report_created']);
    if (!is_array($triggerOptions) || empty($triggerOptions)) {
        $triggerOptions = ['user_registered'];
    }
    $triggerOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $triggerOptions)));
    if (empty($triggerOptions)) {
        $triggerOptions = ['user_registered'];
    }

    $trigger = strtolower(trim((string) ($input['trigger'] ?? $triggerOptions[0])));
    if (!in_array($trigger, $triggerOptions, true)) {
        $trigger = $triggerOptions[0];
    }

    $actionTypes = fg_get_setting('automation_action_types', ['enqueue_notification', 'record_activity', 'update_dataset']);
    if (!is_array($actionTypes) || empty($actionTypes)) {
        $actionTypes = ['enqueue_notification'];
    }
    $actionTypes = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $actionTypes)));
    if (empty($actionTypes)) {
        $actionTypes = ['enqueue_notification'];
    }

    $conditionTypes = fg_get_setting('automation_condition_types', ['custom', 'role_equals', 'dataset_threshold', 'time_window']);
    if (!is_array($conditionTypes) || empty($conditionTypes)) {
        $conditionTypes = ['custom'];
    }
    $conditionTypes = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $conditionTypes)));
    if (empty($conditionTypes)) {
        $conditionTypes = ['custom'];
    }

    $priorityOptions = fg_get_setting('automation_priority_options', ['low', 'medium', 'high']);
    if (!is_array($priorityOptions) || empty($priorityOptions)) {
        $priorityOptions = ['low', 'medium', 'high'];
    }
    $priorityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $priorityOptions)));
    if (empty($priorityOptions)) {
        $priorityOptions = ['medium'];
    }

    $priority = strtolower(trim((string) ($input['priority'] ?? 'medium')));
    if (!in_array($priority, $priorityOptions, true)) {
        $priority = $priorityOptions[0];
    }

    $conditionIssues = [];
    $conditions = fg_parse_automation_lines($input['conditions'] ?? '', $conditionTypes, $conditionTypes[0], $conditionIssues);

    $actionIssues = [];
    $actions = fg_parse_automation_lines($input['actions'] ?? '', $actionTypes, $actionTypes[0], $actionIssues);

    $definitionIssues = [];
    if (!empty($conditionIssues)) {
        $definitionIssues[] = 'Conditions: ' . implode(' ', $conditionIssues);
    }
    if (!empty($actionIssues)) {
        $definitionIssues[] = 'Actions: ' . implode(' ', $actionIssues);
    }
    if (!empty($definitionIssues)) {
        throw new InvalidArgumentException('Automation definition issues detected. ' . implode(' ', $definitionIssues));
    }

    if (empty($actions)) {
        throw new InvalidArgumentException('At least one automation action is required.');
    }

    $ownerRole = strtolower(trim((string) ($input['owner_role'] ?? fg_get_setting('automation_default_owner_role', 'admin'))));
    if ($ownerRole === '') {
        $ownerRole = null;
    }

    $ownerUserId = $input['owner_user_id'] ?? null;
    if ($ownerUserId !== null) {
        $ownerUserId = (int) $ownerUserId;
        if ($ownerUserId <= 0) {
            $ownerUserId = null;
        }
    }

    $runLimit = $input['run_limit'] ?? null;
    if ($runLimit === '' || $runLimit === null) {
        $runLimit = null;
    } else {
        $runLimit = (int) $runLimit;
        if ($runLimit <= 0) {
            $runLimit = null;
        }
    }

    $tagsInput = $input['tags'] ?? [];
    if (!is_array($tagsInput)) {
        $tagsInput = preg_split('/[,\n]+/', (string) $tagsInput) ?: [];
    }
    $tags = [];
    foreach ($tagsInput as $tag) {
        $normalized = strtolower(trim((string) $tag));
        if ($normalized !== '' && !in_array($normalized, $tags, true)) {
            $tags[] = $normalized;
        }
    }

    try {
        $dataset = fg_load_automations();
    } catch (Throwable $exception) {
        $dataset = fg_default_automations_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_automations_dataset();
    }

    $nextId = (int) ($dataset['next_id'] ?? 1);
    if ($nextId < 1) {
        $nextId = 1;
    }

    $now = date(DATE_ATOM);
    $record = [
        'id' => $nextId,
        'name' => $name,
        'description' => $description,
        'status' => $status,
        'trigger' => $trigger,
        'conditions' => $conditions,
        'actions' => $actions,
        'owner_role' => $ownerRole,
        'owner_user_id' => $ownerUserId,
        'run_limit' => $runLimit,
        'run_count' => 0,
        'priority' => $priority,
        'tags' => $tags,
        'created_at' => $now,
        'updated_at' => $now,
        'last_run_at' => null,
    ];

    $dataset['records'][] = $record;
    $dataset['next_id'] = $nextId + 1;

    $performedBy = $context['performed_by'] ?? ($input['performed_by'] ?? null);
    if ($performedBy !== null) {
        $performedBy = (int) $performedBy;
        if ($performedBy <= 0) {
            $performedBy = null;
        }
    }

    $saveContext = $context;
    if ($performedBy !== null && !isset($saveContext['performed_by'])) {
        $saveContext['performed_by'] = $performedBy;
    }
    $saveContext['record_id'] = $record['id'];
    $saveContext['trigger'] = $saveContext['trigger'] ?? 'automation_created';

    fg_save_automations($dataset, 'Create automation', $saveContext);

    return $record;
}

