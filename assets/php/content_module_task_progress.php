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
    $overdueCount = 0;
    $dueSoonCount = 0;
    $overdueWeight = 0.0;
    $dueSoonWeight = 0.0;

    $todayStart = strtotime('today');
    if ($todayStart === false) {
        $todayStart = strtotime(date('Y-m-d'));
    }
    if ($todayStart === false) {
        $todayStart = time();
    }
    $soonThreshold = $todayStart + (3 * 86400);

    foreach ($tasks as $task) {
        $label = '';
        $description = '';
        $completed = false;
        $weight = 1.0;
        $dueTimestamp = null;

        if (is_string($task)) {
            $parts = array_map(static function ($segment) {
                return trim((string) $segment);
            }, explode('|', $task));
            $label = $parts[0] ?? '';
            $description = $parts[1] ?? '';
            $statusHint = strtolower(trim((string) ($parts[2] ?? '')));
            if ($statusHint !== '') {
                $completed = in_array($statusHint, ['1', 'true', 'yes', 'y', 'on', 'complete', 'completed', 'done', 'finished', 'checked'], true);
            }
            $dueCandidate = $parts[4] ?? '';
            if ($dueCandidate !== '') {
                $parsedDue = strtotime($dueCandidate);
                if ($parsedDue !== false) {
                    $dueTimestamp = $parsedDue;
                }
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

            if (isset($task['due_timestamp']) && is_numeric($task['due_timestamp'])) {
                $dueTimestamp = (int) $task['due_timestamp'];
            } elseif (!empty($task['due_date']) || !empty($task['due']) || !empty($task['deadline'])) {
                $dueCandidate = (string) ($task['due_date'] ?? $task['due'] ?? $task['deadline']);
                $parsedDue = strtotime($dueCandidate);
                if ($parsedDue !== false) {
                    $dueTimestamp = $parsedDue;
                }
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
        } else {
            if ($dueTimestamp !== null) {
                if ($dueTimestamp < $todayStart) {
                    $overdueCount++;
                    $overdueWeight += $weight;
                } elseif ($dueTimestamp <= $soonThreshold) {
                    $dueSoonCount++;
                    $dueSoonWeight += $weight;
                }
            }
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
    } elseif ($completedCount >= $totalCount) {
        $state = 'complete';
        $statusLabel = 'Checklist complete';
        $summary = sprintf('All %d task%s complete.', $totalCount, $totalCount === 1 ? '' : 's');
    } else {
        $progressSummary = sprintf('%d of %d task%s complete (%s)', $completedCount, $totalCount, $totalCount === 1 ? '' : 's', $percentLabel);
        $statusBits = [];

        if ($overdueCount > 0) {
            $state = 'overdue';
            $statusLabel = 'Overdue';
            $statusBits[] = sprintf('%d overdue', $overdueCount);
        } elseif ($dueSoonCount > 0) {
            $state = 'due_soon';
            $statusLabel = 'Due soon';
            $statusBits[] = sprintf('%d due soon', $dueSoonCount);
        } elseif ($completedCount === 0) {
            $state = 'not_started';
            $statusLabel = 'Not started';
        } else {
            $state = 'in_progress';
            $statusLabel = 'In progress';
        }

        if ($completedCount === 0 && empty($statusBits)) {
            $summary = sprintf('0 of %d task%s complete.', $totalCount, $totalCount === 1 ? '' : 's');
        } elseif (!empty($statusBits)) {
            $summary = implode(' · ', $statusBits) . ' · ' . $progressSummary . '.';
        } else {
            $summary = $progressSummary . '.';
        }
    }

    return [
        'total' => $totalCount,
        'completed' => $completedCount,
        'pending' => $pendingCount,
        'total_weight' => $totalWeight,
        'completed_weight' => $completedWeight,
        'pending_weight' => $pendingWeight,
        'overdue' => $overdueCount,
        'overdue_weight' => $overdueWeight,
        'due_soon' => $dueSoonCount,
        'due_soon_weight' => $dueSoonWeight,
        'percent_complete' => $percentRounded,
        'percent_label' => $percentLabel,
        'state' => $state,
        'status_label' => $statusLabel,
        'summary' => $summary,
        'has_tasks' => $totalCount > 0,
    ];
}
