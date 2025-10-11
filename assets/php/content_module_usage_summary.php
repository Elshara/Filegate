<?php

require_once __DIR__ . '/list_content_modules.php';
require_once __DIR__ . '/load_posts.php';
require_once __DIR__ . '/normalize_content_module_key.php';
require_once __DIR__ . '/content_module_task_progress.php';

function fg_content_module_usage_summary(array $options = []): array
{
    $dataset = $options['dataset'] ?? null;
    if (is_string($dataset)) {
        $dataset = trim($dataset);
        if ($dataset === '') {
            $dataset = null;
        }
    } else {
        $dataset = null;
    }

    $modulesOption = $options['modules'] ?? null;
    $moduleMap = [];

    if ($modulesOption !== null) {
        if (!is_array($modulesOption)) {
            $modulesOption = [];
        }
        foreach ($modulesOption as $key => $module) {
            if (!is_array($module)) {
                continue;
            }
            $moduleKey = $module['key'] ?? null;
            if (!is_string($moduleKey) || trim($moduleKey) === '') {
                if (is_string($key) && trim($key) !== '') {
                    $moduleKey = $key;
                } else {
                    $moduleKey = $module['label'] ?? '';
                }
            }
            $moduleKey = fg_normalize_content_module_key((string) $moduleKey);
            if ($moduleKey === '') {
                continue;
            }
            $moduleMap[$moduleKey] = $module;
        }
    } else {
        $listOptions = [];
        if (array_key_exists('statuses', $options)) {
            $listOptions['statuses'] = $options['statuses'];
        }
        if (!empty($options['viewer'])) {
            $listOptions['viewer'] = $options['viewer'];
        }
        if (!empty($options['enforce_visibility'])) {
            $listOptions['enforce_visibility'] = true;
        }
        $moduleMap = fg_list_content_modules($dataset, $listOptions);
    }

    $summary = [
        'modules' => [],
        'totals' => [
            'modules' => 0,
            'posts' => 0,
            'posts_completed' => 0,
            'posts_overdue' => 0,
            'posts_due_soon' => 0,
            'tasks' => 0,
            'tasks_completed' => 0,
            'tasks_pending' => 0,
            'attention_modules' => 0,
        ],
    ];

    foreach ($moduleMap as $key => $module) {
        if (!is_array($module)) {
            continue;
        }
        $moduleKey = fg_normalize_content_module_key((string) ($module['key'] ?? $key));
        if ($moduleKey === '') {
            continue;
        }
        $label = trim((string) ($module['label'] ?? ucwords(str_replace('-', ' ', $moduleKey))));
        if ($label === '') {
            $label = ucwords(str_replace('-', ' ', $moduleKey));
        }
        $summary['modules'][$moduleKey] = [
            'key' => $moduleKey,
            'label' => $label,
            'status' => strtolower((string) ($module['status'] ?? 'active')),
            'visibility' => strtolower((string) ($module['visibility'] ?? 'members')),
            'categories' => array_values(array_filter(array_map('strval', $module['categories'] ?? []), static function ($value) {
                return trim($value) !== '';
            })),
            'post_count' => 0,
            'posts_completed' => 0,
            'posts_overdue' => 0,
            'posts_due_soon' => 0,
            'task_total' => 0,
            'task_completed' => 0,
            'task_pending' => 0,
            'percent_accumulated' => 0.0,
            'percent_average' => 0.0,
            'percent_average_label' => '0%',
            'last_activity' => null,
            'last_activity_display' => '',
            'last_activity_post_id' => null,
            'last_activity_post_title' => '',
            'attention_state' => 'idle',
            'attention_label' => 'No active posts',
        ];
    }

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

    foreach ($postRecords as $post) {
        if (!is_array($post) || empty($post['content_module'])) {
            continue;
        }
        $moduleAssignment = $post['content_module'];
        if (!is_array($moduleAssignment)) {
            continue;
        }
        $moduleKeySource = $moduleAssignment['key'] ?? ($moduleAssignment['module'] ?? ($moduleAssignment['label'] ?? ''));
        $moduleKey = fg_normalize_content_module_key((string) $moduleKeySource);
        if ($moduleKey === '' && isset($moduleAssignment['module_key'])) {
            $moduleKey = fg_normalize_content_module_key((string) $moduleAssignment['module_key']);
        }
        if ($moduleKey === '') {
            continue;
        }

        if (!isset($summary['modules'][$moduleKey])) {
            $label = trim((string) ($moduleAssignment['label'] ?? $moduleAssignment['module_label'] ?? ucwords(str_replace('-', ' ', $moduleKey))));
            if ($label === '') {
                $label = ucwords(str_replace('-', ' ', $moduleKey));
            }
            $summary['modules'][$moduleKey] = [
                'key' => $moduleKey,
                'label' => $label,
                'status' => 'unknown',
                'visibility' => 'members',
                'categories' => [],
                'post_count' => 0,
                'posts_completed' => 0,
                'posts_overdue' => 0,
                'posts_due_soon' => 0,
                'task_total' => 0,
                'task_completed' => 0,
                'task_pending' => 0,
                'percent_accumulated' => 0.0,
                'percent_average' => 0.0,
                'percent_average_label' => '0%',
                'last_activity' => null,
                'last_activity_display' => '',
                'last_activity_post_id' => null,
                'last_activity_post_title' => '',
                'attention_state' => 'idle',
                'attention_label' => 'No active posts',
            ];
        }

        $tasks = $moduleAssignment['tasks'] ?? [];
        $taskProgress = $moduleAssignment['task_progress'] ?? null;
        if (!is_array($tasks)) {
            $tasks = [];
        }
        if (!is_array($taskProgress)) {
            $taskProgress = fg_content_module_task_progress($tasks);
        }

        $entry =& $summary['modules'][$moduleKey];
        $entry['post_count']++;
        $entry['task_total'] += (int) ($taskProgress['total'] ?? count($tasks));
        $entry['task_completed'] += (int) ($taskProgress['completed'] ?? 0);
        $entry['task_pending'] += (int) ($taskProgress['pending'] ?? max(0, ($taskProgress['total'] ?? count($tasks)) - ($taskProgress['completed'] ?? 0)));
        $entry['percent_accumulated'] += (float) ($taskProgress['percent_complete'] ?? 0.0);

        $state = (string) ($taskProgress['state'] ?? '');
        if ($state === 'complete') {
            $entry['posts_completed']++;
        } elseif ($state === 'overdue') {
            $entry['posts_overdue']++;
        } elseif ($state === 'due_soon') {
            $entry['posts_due_soon']++;
        }

        $lastUpdated = null;
        if (isset($post['updated_at']) && $post['updated_at'] !== '') {
            if (is_numeric($post['updated_at'])) {
                $lastUpdated = (int) $post['updated_at'];
            } else {
                $timestamp = strtotime((string) $post['updated_at']);
                if ($timestamp !== false) {
                    $lastUpdated = $timestamp;
                }
            }
        }
        if ($lastUpdated === null && isset($post['modified_at']) && $post['modified_at'] !== '') {
            if (is_numeric($post['modified_at'])) {
                $lastUpdated = (int) $post['modified_at'];
            } else {
                $timestamp = strtotime((string) $post['modified_at']);
                if ($timestamp !== false) {
                    $lastUpdated = $timestamp;
                }
            }
        }
        if ($lastUpdated === null && isset($post['created_at']) && $post['created_at'] !== '') {
            if (is_numeric($post['created_at'])) {
                $lastUpdated = (int) $post['created_at'];
            } else {
                $timestamp = strtotime((string) $post['created_at']);
                if ($timestamp !== false) {
                    $lastUpdated = $timestamp;
                }
            }
        }

        if ($lastUpdated !== null && ($entry['last_activity'] === null || $lastUpdated > $entry['last_activity'])) {
            $entry['last_activity'] = $lastUpdated;
            $entry['last_activity_display'] = date('M j, Y g:ia', $lastUpdated);
            $entry['last_activity_post_id'] = $post['id'] ?? null;
            $entry['last_activity_post_title'] = $post['title'] ?? ($post['summary'] ?? 'Post #' . ($post['id'] ?? '?'));
        }
    }

    foreach ($summary['modules'] as $moduleKey => &$entry) {
        if (!is_array($entry)) {
            unset($summary['modules'][$moduleKey]);
            continue;
        }
        $postCount = (int) $entry['post_count'];
        $entry['percent_average'] = $postCount > 0 ? $entry['percent_accumulated'] / $postCount : 0.0;
        $entry['percent_average'] = max(0.0, min(100.0, $entry['percent_average']));
        $entry['percent_average_label'] = rtrim(rtrim(sprintf('%.1f', $entry['percent_average']), '0'), '.');
        if ($entry['percent_average_label'] === '') {
            $entry['percent_average_label'] = '0';
        }
        $entry['percent_average_label'] .= '%';

        $attentionState = 'idle';
        $attentionLabel = 'No active posts';
        if ($postCount > 0) {
            $attentionLabel = sprintf('%d post%s tracked', $postCount, $postCount === 1 ? '' : 's');
            $overduePosts = (int) $entry['posts_overdue'];
            $dueSoonPosts = (int) $entry['posts_due_soon'];
            $completedPosts = (int) $entry['posts_completed'];
            if ($overduePosts > 0) {
                $attentionState = 'overdue';
                $attentionLabel = sprintf('%d post%s overdue', $overduePosts, $overduePosts === 1 ? '' : 's');
            } elseif ($dueSoonPosts > 0) {
                $attentionState = 'due_soon';
                $attentionLabel = sprintf('%d post%s due soon', $dueSoonPosts, $dueSoonPosts === 1 ? '' : 's');
            } elseif ($completedPosts >= $postCount) {
                $attentionState = 'complete';
                $attentionLabel = 'All checklists complete';
            } else {
                $attentionState = 'in_progress';
                $attentionLabel = sprintf('%s avg completion', $entry['percent_average_label']);
            }
        }
        $entry['attention_state'] = $attentionState;
        $entry['attention_label'] = $attentionLabel;

        $summary['totals']['modules']++;
        $summary['totals']['posts'] += $postCount;
        $summary['totals']['posts_completed'] += (int) $entry['posts_completed'];
        $summary['totals']['posts_overdue'] += (int) $entry['posts_overdue'];
        $summary['totals']['posts_due_soon'] += (int) $entry['posts_due_soon'];
        $summary['totals']['tasks'] += (int) $entry['task_total'];
        $summary['totals']['tasks_completed'] += (int) $entry['task_completed'];
        $summary['totals']['tasks_pending'] += (int) $entry['task_pending'];
        if (in_array($entry['attention_state'], ['overdue', 'due_soon'], true)) {
            $summary['totals']['attention_modules']++;
        }

        unset($entry['percent_accumulated']);
    }
    unset($entry);

    return $summary;
}
