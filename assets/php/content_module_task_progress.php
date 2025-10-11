<?php

function fg_content_module_task_progress($tasks): array
{
    if (is_string($tasks)) {
        $tasks = preg_split('/\R+/u', $tasks) ?: [];
    }

    if (!is_array($tasks)) {
        $tasks = [];
    }

    $totalCount = 0;
    $completedCount = 0;
    $totalWeight = 0.0;
    $completedWeight = 0.0;

    foreach ($tasks as $task) {
        $label = '';
        $description = '';
        $completed = false;
        $weight = 1.0;

        if (is_string($task)) {
            $parts = explode('|', $task, 3);
            $label = trim((string) ($parts[0] ?? ''));
            $description = trim((string) ($parts[1] ?? ''));
            $statusHint = strtolower(trim((string) ($parts[2] ?? '')));
            if ($statusHint !== '') {
                $completed = in_array($statusHint, ['1', 'true', 'yes', 'y', 'on', 'complete', 'completed', 'done', 'finished', 'checked'], true);
            }
        } elseif (is_array($task)) {
            $label = trim((string) ($task['label'] ?? $task['title'] ?? ''));
            $description = trim((string) ($task['description'] ?? $task['prompt'] ?? $task['notes'] ?? ''));
            if (isset($task['completed'])) {
                $completed = (bool) $task['completed'];
            } elseif (isset($task['status'])) {
                $statusHint = strtolower(trim((string) $task['status']));
                $completed = in_array($statusHint, ['complete', 'completed', 'done', 'finished', 'checked', 'true', '1', 'yes', 'on'], true);
            } elseif (isset($task['state'])) {
                $statusHint = strtolower(trim((string) $task['state']));
                $completed = in_array($statusHint, ['complete', 'completed', 'done', 'finished', 'checked', 'true', '1', 'yes', 'on'], true);
            } elseif (isset($task['default'])) {
                $defaultHint = strtolower(trim((string) $task['default']));
                $completed = in_array($defaultHint, ['complete', 'completed', 'done', 'finished', 'checked', 'true', '1', 'yes', 'on'], true);
            }

            if (isset($task['weight'])) {
                $weight = (float) $task['weight'];
            } elseif (isset($task['points'])) {
                $weight = (float) $task['points'];
            } elseif (isset($task['value'])) {
                $weight = (float) $task['value'];
            }
        } else {
            continue;
        }

        if ($label === '' && $description === '') {
            continue;
        }

        if (!is_finite($weight) || $weight <= 0) {
            $weight = 1.0;
        }

        $totalCount++;
        $totalWeight += $weight;

        if ($completed) {
            $completedCount++;
            $completedWeight += $weight;
        }
    }

    $pendingCount = max(0, $totalCount - $completedCount);
    $pendingWeight = max(0.0, $totalWeight - $completedWeight);

    if ($totalWeight <= 0) {
        $percentComplete = $totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0.0;
    } else {
        $percentComplete = ($completedWeight / $totalWeight) * 100;
    }

    $percentComplete = max(0.0, min(100.0, $percentComplete));
    $percentRounded = round($percentComplete, 1);
    $percentLabel = rtrim(rtrim(sprintf('%.1f', $percentRounded), '0'), '.');
    if ($percentLabel === '') {
        $percentLabel = '0';
    }
    $percentLabel .= '%';

    if ($totalCount === 0) {
        $state = 'empty';
        $statusLabel = 'No checklist items';
        $summary = 'Add checklist tasks to guide contributors.';
    } elseif ($completedCount === 0) {
        $state = 'not_started';
        $statusLabel = 'Not started';
        $summary = sprintf('0 of %d task%s complete.', $totalCount, $totalCount === 1 ? '' : 's');
    } elseif ($completedCount >= $totalCount) {
        $state = 'complete';
        $statusLabel = 'Checklist complete';
        $summary = sprintf('All %d task%s complete.', $totalCount, $totalCount === 1 ? '' : 's');
    } else {
        $state = 'in_progress';
        $statusLabel = 'In progress';
        $summary = sprintf('%d of %d task%s complete (%s).', $completedCount, $totalCount, $totalCount === 1 ? '' : 's', $percentLabel);
    }

    return [
        'total' => $totalCount,
        'completed' => $completedCount,
        'pending' => $pendingCount,
        'total_weight' => $totalWeight,
        'completed_weight' => $completedWeight,
        'pending_weight' => $pendingWeight,
        'percent_complete' => $percentRounded,
        'percent_label' => $percentLabel,
        'state' => $state,
        'status_label' => $statusLabel,
        'summary' => $summary,
        'has_tasks' => $totalCount > 0,
    ];
}
