<?php

require_once __DIR__ . '/load_posts.php';
require_once __DIR__ . '/normalize_content_module_key.php';

function fg_content_module_task_assignments(array $options = []): array
{
    $postsData = $options['posts'] ?? null;
    if ($postsData === null) {
        $postsData = fg_load_posts();
    }

    if (isset($postsData['records']) && is_array($postsData['records'])) {
        $postRecords = $postsData['records'];
    } elseif (is_array($postsData)) {
        $postRecords = $postsData;
    } else {
        $postRecords = [];
    }

    $moduleMap = [];
    $modulesOption = $options['modules'] ?? null;
    if (is_array($modulesOption)) {
        foreach ($modulesOption as $key => $module) {
            if (!is_array($module)) {
                continue;
            }
            $moduleKey = $module['key'] ?? null;
            if (!is_string($moduleKey) || trim($moduleKey) === '') {
                if (is_string($key) && trim($key) !== '') {
                    $moduleKey = $key;
                } elseif (isset($module['label']) && is_string($module['label'])) {
                    $moduleKey = $module['label'];
                }
            }
            $moduleKey = fg_normalize_content_module_key((string) $moduleKey);
            if ($moduleKey === '') {
                continue;
            }
            $moduleMap[$moduleKey] = [
                'label' => trim((string) ($module['label'] ?? ucwords(str_replace('-', ' ', $moduleKey)))),
            ];
        }
    }

    $todayStart = strtotime('today');
    if ($todayStart === false) {
        $todayStart = strtotime(date('Y-m-d'));
    }
    if ($todayStart === false) {
        $todayStart = time();
    }
    $soonThreshold = $todayStart + (3 * 86400);

    $summary = [
        'owners' => [],
        'totals' => [
            'owners' => 0,
            'tasks' => 0,
            'tasks_completed' => 0,
            'tasks_pending' => 0,
            'tasks_due_soon' => 0,
            'tasks_overdue' => 0,
            'attention_tasks' => 0,
            'posts' => 0,
            'modules' => 0,
        ],
    ];

    $trackedPosts = [];
    $trackedModules = [];

    foreach ($postRecords as $post) {
        if (!is_array($post)) {
            continue;
        }
        $moduleAssignment = $post['content_module'] ?? null;
        if (!is_array($moduleAssignment)) {
            continue;
        }
        $moduleKeySource = $moduleAssignment['key'] ?? ($moduleAssignment['module'] ?? ($moduleAssignment['module_key'] ?? ''));
        $moduleKey = fg_normalize_content_module_key((string) $moduleKeySource);
        if ($moduleKey === '') {
            continue;
        }

        $moduleLabel = trim((string) ($moduleAssignment['label'] ?? ''));
        if ($moduleLabel === '' && isset($moduleMap[$moduleKey])) {
            $moduleLabel = trim((string) ($moduleMap[$moduleKey]['label'] ?? ''));
        }
        if ($moduleLabel === '') {
            $moduleLabel = ucwords(str_replace('-', ' ', $moduleKey));
        }

        $tasks = $moduleAssignment['tasks'] ?? [];
        if (!is_array($tasks) || empty($tasks)) {
            continue;
        }

        $postId = (int) ($post['id'] ?? 0);
        $postLabel = trim((string) ($post['custom_type'] ?? ''));
        if ($postLabel === '') {
            $postLabel = trim((string) ($post['title'] ?? ''));
        }
        if ($postLabel === '') {
            $postLabel = trim((string) ($post['summary'] ?? ''));
        }
        if ($postLabel === '') {
            $postLabel = $postId > 0 ? 'Post #' . $postId : 'Post';
        }

        $trackedPosts[$postId] = true;
        $trackedModules[$moduleKey] = true;

        foreach ($tasks as $task) {
            if (!is_array($task)) {
                continue;
            }
            $taskLabel = trim((string) ($task['label'] ?? ''));
            $taskDescription = trim((string) ($task['description'] ?? ''));
            if ($taskLabel === '' && $taskDescription === '') {
                continue;
            }
            $taskKeySource = $task['key'] ?? $taskLabel;
            $taskKey = fg_normalize_content_module_key((string) $taskKeySource);
            if ($taskKey === '') {
                $taskKey = fg_normalize_content_module_key($taskLabel !== '' ? $taskLabel : $taskDescription);
            }
            if ($taskKey === '') {
                $taskKey = 'task-' . ($summary['totals']['tasks'] + 1);
            }

            $ownerName = trim((string) ($task['owner'] ?? ''));
            $ownerKey = fg_normalize_content_module_key($ownerName);
            if ($ownerKey === '') {
                $ownerKey = 'unassigned';
            }
            $ownerLabel = $ownerName !== '' ? $ownerName : 'Unassigned';

            $dueTimestamp = null;
            if (isset($task['due_timestamp']) && is_numeric($task['due_timestamp'])) {
                $dueTimestamp = (int) $task['due_timestamp'];
            } elseif (!empty($task['due_date'])) {
                $parsed = strtotime((string) $task['due_date']);
                if ($parsed !== false) {
                    $dueTimestamp = $parsed;
                }
            } elseif (!empty($task['due_display'])) {
                $parsed = strtotime((string) $task['due_display']);
                if ($parsed !== false) {
                    $dueTimestamp = $parsed;
                }
            }

            $dueDate = trim((string) ($task['due_date'] ?? ''));
            $dueDisplay = trim((string) ($task['due_display'] ?? ''));
            if ($dueDisplay === '' && $dueDate !== '') {
                $dueDisplay = $dueDate;
            }
            if ($dueTimestamp !== null && $dueDisplay === '') {
                $dueDisplay = date('M j, Y', $dueTimestamp);
            }

            $priority = trim((string) ($task['priority'] ?? ''));
            $priorityLabel = trim((string) ($task['priority_label'] ?? ''));
            if ($priorityLabel === '' && $priority !== '') {
                $priorityLabel = ucfirst($priority);
            }
            $notes = trim((string) ($task['notes'] ?? ''));
            $completed = !empty($task['completed']);
            $weight = isset($task['weight']) && is_numeric($task['weight']) ? (float) $task['weight'] : 1.0;

            $state = 'pending';
            $stateLabel = 'Pending';
            if ($completed) {
                $state = 'complete';
                $stateLabel = 'Completed';
            } elseif ($dueTimestamp !== null) {
                if ($dueTimestamp < $todayStart) {
                    $state = 'overdue';
                    $stateLabel = 'Overdue';
                } elseif ($dueTimestamp <= $soonThreshold) {
                    $state = 'due-soon';
                    $stateLabel = 'Due soon';
                }
            }

            if (!isset($summary['owners'][$ownerKey])) {
                $ownerTokens = [];
                if ($ownerName !== '') {
                    $ownerTokens[] = strtolower($ownerName);
                    $ownerTokens[] = fg_normalize_content_module_key($ownerName);
                } else {
                    $ownerTokens[] = 'unassigned';
                }
                $ownerTokens = array_values(array_unique(array_filter($ownerTokens, static function ($token) {
                    return is_string($token) && $token !== '';
                })));

                $summary['owners'][$ownerKey] = [
                    'key' => $ownerKey,
                    'label' => $ownerLabel,
                    'match_tokens' => $ownerTokens,
                    'tasks_total' => 0,
                    'tasks_completed' => 0,
                    'tasks_pending' => 0,
                    'tasks_due_soon' => 0,
                    'tasks_overdue' => 0,
                    'tasks_weight' => 0.0,
                    'posts' => [],
                    'modules' => [],
                    'tasks' => [],
                    'attention_tasks' => [],
                    'attention_count' => 0,
                    'earliest_due' => null,
                    'latest_due' => null,
                ];
            }

            $ownerEntry =& $summary['owners'][$ownerKey];
            $ownerEntry['tasks_total']++;
            $ownerEntry['tasks_weight'] += $weight;
            if ($completed) {
                $ownerEntry['tasks_completed']++;
            } else {
                $ownerEntry['tasks_pending']++;
                if ($state === 'overdue') {
                    $ownerEntry['tasks_overdue']++;
                } elseif ($state === 'due-soon') {
                    $ownerEntry['tasks_due_soon']++;
                }
            }
            if ($dueTimestamp !== null) {
                if ($ownerEntry['earliest_due'] === null || $dueTimestamp < $ownerEntry['earliest_due']) {
                    $ownerEntry['earliest_due'] = $dueTimestamp;
                }
                if ($ownerEntry['latest_due'] === null || $dueTimestamp > $ownerEntry['latest_due']) {
                    $ownerEntry['latest_due'] = $dueTimestamp;
                }
            }

            $ownerEntry['posts'][$postId] = true;
            $ownerEntry['modules'][$moduleKey] = true;

            $taskEntry = [
                'task_key' => $taskKey,
                'label' => $taskLabel !== '' ? $taskLabel : $taskDescription,
                'description' => $taskDescription,
                'completed' => $completed,
                'state' => $state,
                'state_label' => $stateLabel,
                'due_timestamp' => $dueTimestamp,
                'due_display' => $dueDisplay,
                'priority' => $priority,
                'priority_label' => $priorityLabel,
                'notes' => $notes,
                'module_key' => $moduleKey,
                'module_label' => $moduleLabel,
                'module_stage' => trim((string) ($moduleAssignment['stage'] ?? '')),
                'post_id' => $postId,
                'post_label' => $postLabel,
                'post_url' => $postId > 0 ? '/index.php#post-' . $postId : '',
                'weight' => $weight,
            ];

            $ownerEntry['tasks'][] = $taskEntry;
            if (!$completed && in_array($state, ['overdue', 'due-soon'], true)) {
                $ownerEntry['attention_tasks'][] = $taskEntry;
                $ownerEntry['attention_count']++;
                $summary['totals']['attention_tasks']++;
            }
            unset($ownerEntry);

            $summary['totals']['tasks']++;
            if ($completed) {
                $summary['totals']['tasks_completed']++;
            } else {
                $summary['totals']['tasks_pending']++;
                if ($state === 'overdue') {
                    $summary['totals']['tasks_overdue']++;
                } elseif ($state === 'due-soon') {
                    $summary['totals']['tasks_due_soon']++;
                }
            }
        }
    }

    $stateOrder = [
        'overdue' => 0,
        'due-soon' => 1,
        'pending' => 2,
        'complete' => 3,
    ];

    foreach ($summary['owners'] as &$ownerEntry) {
        $ownerEntry['post_count'] = count($ownerEntry['posts']);
        $ownerEntry['module_count'] = count($ownerEntry['modules']);
        unset($ownerEntry['posts'], $ownerEntry['modules']);

        usort($ownerEntry['tasks'], static function ($a, $b) use ($stateOrder) {
            $stateA = $stateOrder[$a['state'] ?? 'pending'] ?? 4;
            $stateB = $stateOrder[$b['state'] ?? 'pending'] ?? 4;
            if ($stateA !== $stateB) {
                return $stateA <=> $stateB;
            }
            $dueA = $a['due_timestamp'] ?? PHP_INT_MAX;
            $dueB = $b['due_timestamp'] ?? PHP_INT_MAX;
            if ($dueA !== $dueB) {
                return $dueA <=> $dueB;
            }
            return strcmp(strtolower((string) ($a['label'] ?? '')), strtolower((string) ($b['label'] ?? '')));
        });

        $ownerEntry['attention_tasks'] = array_values(array_filter($ownerEntry['attention_tasks'], static function ($task) {
            return is_array($task);
        }));

        if ($ownerEntry['earliest_due'] !== null) {
            $ownerEntry['earliest_due_display'] = date('M j, Y', $ownerEntry['earliest_due']);
        } else {
            $ownerEntry['earliest_due_display'] = '';
        }
        if ($ownerEntry['latest_due'] !== null) {
            $ownerEntry['latest_due_display'] = date('M j, Y', $ownerEntry['latest_due']);
        } else {
            $ownerEntry['latest_due_display'] = '';
        }
    }
    unset($ownerEntry);

    uasort($summary['owners'], static function ($a, $b) {
        $overdueA = $a['tasks_overdue'] ?? 0;
        $overdueB = $b['tasks_overdue'] ?? 0;
        if ($overdueA !== $overdueB) {
            return $overdueB <=> $overdueA;
        }
        $dueSoonA = $a['tasks_due_soon'] ?? 0;
        $dueSoonB = $b['tasks_due_soon'] ?? 0;
        if ($dueSoonA !== $dueSoonB) {
            return $dueSoonB <=> $dueSoonA;
        }
        $pendingA = $a['tasks_pending'] ?? 0;
        $pendingB = $b['tasks_pending'] ?? 0;
        if ($pendingA !== $pendingB) {
            return $pendingB <=> $pendingA;
        }
        return strcmp(strtolower((string) ($a['label'] ?? '')), strtolower((string) ($b['label'] ?? '')));
    });

    $summary['totals']['owners'] = count($summary['owners']);
    $summary['totals']['posts'] = count(array_filter(array_keys($trackedPosts), static function ($value) {
        return $value !== 0;
    }));
    $summary['totals']['modules'] = count($trackedModules);

    return $summary;
}
