<?php

require_once __DIR__ . '/load_automations.php';
require_once __DIR__ . '/save_automations.php';
require_once __DIR__ . '/default_automations_dataset.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/parse_automation_lines.php';

function fg_update_automation(int $automationId, array $input, array $context = []): array
{
    if ($automationId <= 0) {
        throw new InvalidArgumentException('A valid automation identifier is required.');
    }

    try {
        $dataset = fg_load_automations();
    } catch (Throwable $exception) {
        $dataset = fg_default_automations_dataset();
    }

    if (!isset($dataset['records']) || !is_array($dataset['records'])) {
        $dataset = fg_default_automations_dataset();
    }

    $index = null;
    foreach ($dataset['records'] as $key => $record) {
        if ((int) ($record['id'] ?? 0) === $automationId) {
            $index = $key;
            break;
        }
    }

    if ($index === null) {
        throw new InvalidArgumentException('The requested automation could not be found.');
    }

    $record = $dataset['records'][$index];

    $nameInput = trim((string) ($input['name'] ?? ($record['name'] ?? '')));
    if ($nameInput === '') {
        throw new InvalidArgumentException('Automation name cannot be empty.');
    }

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

    $currentStatus = strtolower((string) ($record['status'] ?? $statusOptions[0]));
    if (!in_array($currentStatus, $statusOptions, true)) {
        $currentStatus = $statusOptions[0];
    }

    $status = strtolower(trim((string) ($input['status'] ?? $currentStatus)));
    if (!in_array($status, $statusOptions, true)) {
        $status = $currentStatus;
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

    $currentTrigger = strtolower((string) ($record['trigger'] ?? $triggerOptions[0]));
    if (!in_array($currentTrigger, $triggerOptions, true)) {
        $currentTrigger = $triggerOptions[0];
    }

    $trigger = strtolower(trim((string) ($input['trigger'] ?? $currentTrigger)));
    if (!in_array($trigger, $triggerOptions, true)) {
        $trigger = $currentTrigger;
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

    $priority = strtolower(trim((string) ($input['priority'] ?? ($record['priority'] ?? $priorityOptions[0]))));
    if (!in_array($priority, $priorityOptions, true)) {
        $priority = $record['priority'] ?? $priorityOptions[0];
        $priority = strtolower((string) $priority);
        if (!in_array($priority, $priorityOptions, true)) {
            $priority = $priorityOptions[0];
        }
    }

    $conditionsInput = $input['conditions'] ?? $record['conditions'] ?? [];
    $conditionIssues = [];
    $conditions = fg_parse_automation_lines($conditionsInput, $conditionTypes, $conditionTypes[0], $conditionIssues);

    $actionsInput = $input['actions'] ?? $record['actions'] ?? [];
    $actionIssues = [];
    $actions = fg_parse_automation_lines($actionsInput, $actionTypes, $actionTypes[0], $actionIssues);

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
        throw new InvalidArgumentException('Automations must include at least one action.');
    }

    $ownerRoleInput = $input['owner_role'] ?? ($record['owner_role'] ?? null);
    $ownerRole = $ownerRoleInput !== null ? strtolower(trim((string) $ownerRoleInput)) : null;
    if ($ownerRole === '') {
        $ownerRole = null;
    }

    $ownerUserIdInput = $input['owner_user_id'] ?? ($record['owner_user_id'] ?? null);
    if ($ownerUserIdInput !== null && $ownerUserIdInput !== '') {
        $ownerUserIdInput = (int) $ownerUserIdInput;
        $ownerUserId = $ownerUserIdInput > 0 ? $ownerUserIdInput : null;
    } else {
        $ownerUserId = null;
    }

    $runLimitInput = $input['run_limit'] ?? ($record['run_limit'] ?? null);
    if ($runLimitInput === '' || $runLimitInput === null) {
        $runLimit = null;
    } else {
        $runLimit = (int) $runLimitInput;
        if ($runLimit <= 0) {
            $runLimit = null;
        }
    }

    $runCountInput = $input['run_count'] ?? ($record['run_count'] ?? 0);
    $runCount = (int) $runCountInput;
    if ($runCount < 0) {
        $runCount = 0;
    }

    $lastRunAtInput = $input['last_run_at'] ?? ($record['last_run_at'] ?? null);
    $lastRunAt = null;
    if (is_string($lastRunAtInput)) {
        $lastRunAtInput = trim($lastRunAtInput);
        if ($lastRunAtInput !== '') {
            $timestamp = strtotime($lastRunAtInput);
            if ($timestamp !== false) {
                $lastRunAt = date(DATE_ATOM, $timestamp);
            }
        }
    } elseif ($lastRunAtInput instanceof DateTimeInterface) {
        $lastRunAt = $lastRunAtInput->format(DATE_ATOM);
    }

    if ($lastRunAt === null && !empty($record['last_run_at'])) {
        $lastRunAt = (string) $record['last_run_at'];
    }

    $tagsInput = $input['tags'] ?? ($record['tags'] ?? []);
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

    $descriptionInput = trim((string) ($input['description'] ?? ($record['description'] ?? '')));

    $dataset['records'][$index] = array_merge($record, [
        'name' => $nameInput,
        'description' => $descriptionInput,
        'status' => $status,
        'trigger' => $trigger,
        'conditions' => $conditions,
        'actions' => $actions,
        'owner_role' => $ownerRole,
        'owner_user_id' => $ownerUserId,
        'run_limit' => $runLimit,
        'run_count' => $runCount,
        'priority' => $priority,
        'tags' => $tags,
        'last_run_at' => $lastRunAt,
        'updated_at' => date(DATE_ATOM),
    ]);

    $saveContext = $context;
    $saveContext['record_id'] = $automationId;
    $saveContext['trigger'] = $saveContext['trigger'] ?? 'automation_updated';
    if (isset($input['performed_by']) && !isset($saveContext['performed_by'])) {
        $performed = (int) $input['performed_by'];
        if ($performed > 0) {
            $saveContext['performed_by'] = $performed;
        }
    }

    fg_save_automations($dataset, 'Update automation', $saveContext);

    return $dataset['records'][$index];
}

