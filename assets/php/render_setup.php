<?php

require_once __DIR__ . '/render_layout.php';
require_once __DIR__ . '/default_translations_dataset.php';
require_once __DIR__ . '/content_module_task_progress.php';
require_once __DIR__ . '/normalize_content_module.php';

function fg_render_setup_page(array $data = []): void
{
    $configurations = $data['configurations']['records'] ?? [];
    $overrides = $data['overrides']['records'] ?? ['global' => [], 'roles' => [], 'users' => []];
    $roles = $data['roles'] ?? [];
    $users = $data['users'] ?? [];
    $userDirectory = [];
    foreach ($users as $user) {
        if (!is_array($user)) {
            continue;
        }
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }
        $username = trim((string) ($user['username'] ?? 'User #' . $userId));
        if ($username === '') {
            $username = 'User #' . $userId;
        }
        $roleLabel = trim((string) ($user['role'] ?? ''));
        if ($roleLabel !== '') {
            $username .= ' · ' . ucfirst($roleLabel);
        }
        $userDirectory[$userId] = $username;
    }
    $datasets = $data['datasets'] ?? [];
    $themes = $data['themes']['records'] ?? [];
    $themeTokens = $data['theme_tokens']['tokens'] ?? [];
    $defaultTheme = $data['default_theme'] ?? '';
    $themePolicy = $data['theme_policy'] ?? 'enabled';
    $translations = $data['translations'] ?? [];
    $translationTokens = $translations['tokens'] ?? [];
    $translationLocales = $translations['locales'] ?? [];
    $fallbackLocale = $translations['fallback_locale'] ?? 'en';
    $defaultTranslations = fg_default_translations_dataset();
    $defaultTranslationTokens = $defaultTranslations['tokens'] ?? [];
    $localePolicy = $data['locale_policy'] ?? 'enabled';
    $defaultLocaleSetting = $data['default_locale'] ?? $fallbackLocale;
    $pagesDataset = $data['pages'] ?? ['records' => [], 'next_id' => 1];
    $pageRecords = $pagesDataset['records'] ?? [];
    $projectStatusDataset = $data['project_status'] ?? ['records' => [], 'next_id' => 1];
    if (!isset($projectStatusDataset['records']) || !is_array($projectStatusDataset['records'])) {
        $projectStatusDataset['records'] = [];
    }
    $projectStatusRecords = $projectStatusDataset['records'];
    $changelogDataset = $data['changelog'] ?? ['records' => [], 'next_id' => 1];
    if (!isset($changelogDataset['records']) || !is_array($changelogDataset['records'])) {
        $changelogDataset['records'] = [];
    }
    $changelogRecords = $changelogDataset['records'];
    $featureRequestDataset = $data['feature_requests'] ?? ['records' => [], 'next_id' => 1];
    if (!isset($featureRequestDataset['records']) || !is_array($featureRequestDataset['records'])) {
        $featureRequestDataset['records'] = [];
    }
    $featureRequestRecords = $featureRequestDataset['records'];
    $bugReportDataset = $data['bug_reports'] ?? ['records' => [], 'next_id' => 1];
    if (!isset($bugReportDataset['records']) || !is_array($bugReportDataset['records'])) {
        $bugReportDataset['records'] = [];
    }
    $bugReportRecords = $bugReportDataset['records'];
    $pollDataset = $data['polls'] ?? ['records' => [], 'next_id' => 1];
    if (!isset($pollDataset['records']) || !is_array($pollDataset['records'])) {
        $pollDataset['records'] = [];
    }
    $pollRecords = $pollDataset['records'];
    $eventDataset = $data['events'] ?? ['records' => [], 'next_id' => 1];
    if (!isset($eventDataset['records']) || !is_array($eventDataset['records'])) {
        $eventDataset['records'] = [];
    }
    $eventRecords = $eventDataset['records'];
    $automationDataset = $data['automations'] ?? ['records' => [], 'next_id' => 1];
    if (!isset($automationDataset['records']) || !is_array($automationDataset['records'])) {
        $automationDataset['records'] = [];
    }
    $automationRecords = $automationDataset['records'];
    $knowledgeDataset = $data['knowledge_base'] ?? ['records' => [], 'next_id' => 1];
    if (!isset($knowledgeDataset['records']) || !is_array($knowledgeDataset['records'])) {
        $knowledgeDataset['records'] = [];
    }
    $knowledgeRecords = $knowledgeDataset['records'];
    $knowledgeCategoryDataset = $data['knowledge_categories'] ?? ['records' => [], 'next_id' => 1];
    if (!isset($knowledgeCategoryDataset['records']) || !is_array($knowledgeCategoryDataset['records'])) {
        $knowledgeCategoryDataset['records'] = [];
    }
    $knowledgeCategoryRecords = $knowledgeCategoryDataset['records'];
    $knowledgeCategoriesSorted = $knowledgeCategoryRecords;
    if (!empty($knowledgeCategoriesSorted)) {
        usort($knowledgeCategoriesSorted, static function ($a, $b) {
            $orderA = (int) ($a['ordering'] ?? 0);
            $orderB = (int) ($b['ordering'] ?? 0);
            if ($orderA === $orderB) {
                return strcmp(strtolower((string) ($a['name'] ?? '')), strtolower((string) ($b['name'] ?? '')));
            }
            return $orderA <=> $orderB;
        });
    }
    $automationStatusOptions = $data['automation_statuses'] ?? ['enabled', 'paused', 'disabled'];
    if (!is_array($automationStatusOptions) || empty($automationStatusOptions)) {
        $automationStatusOptions = ['enabled', 'paused', 'disabled'];
    }
    $automationStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $automationStatusOptions)));
    if (empty($automationStatusOptions)) {
        $automationStatusOptions = ['enabled'];
    }
    $automationDefaultStatus = strtolower((string) ($data['automation_default_status'] ?? $automationStatusOptions[0]));
    if (!in_array($automationDefaultStatus, $automationStatusOptions, true)) {
        $automationDefaultStatus = $automationStatusOptions[0];
    }
    $automationStatusLabels = [];
    foreach ($automationStatusOptions as $statusValue) {
        $automationStatusLabels[$statusValue] = ucwords(str_replace('_', ' ', $statusValue));
    }

    $contentModuleDataset = $data['content_modules'] ?? ['records' => [], 'next_id' => 1];
    if (!isset($contentModuleDataset['records']) || !is_array($contentModuleDataset['records'])) {
        $contentModuleDataset['records'] = [];
    }
    $contentModuleRecords = $contentModuleDataset['records'];
    $contentBlueprints = $data['content_blueprints'] ?? [];
    $contentModuleUsage = $data['content_module_usage'] ?? ['modules' => [], 'totals' => []];
    if (!is_array($contentModuleUsage)) {
        $contentModuleUsage = ['modules' => [], 'totals' => []];
    }
    $contentModuleUsageModules = $contentModuleUsage['modules'] ?? [];
    if (!is_array($contentModuleUsageModules)) {
        $contentModuleUsageModules = [];
    }
    $contentModuleUsageTotals = $contentModuleUsage['totals'] ?? [];
    if (!is_array($contentModuleUsageTotals)) {
        $contentModuleUsageTotals = [];
    }
    $contentModuleAssignments = $data['content_module_assignments'] ?? ['owners' => [], 'totals' => []];
    if (!is_array($contentModuleAssignments)) {
        $contentModuleAssignments = ['owners' => [], 'totals' => []];
    }
    $contentModuleAssignmentOwners = $contentModuleAssignments['owners'] ?? [];
    if (!is_array($contentModuleAssignmentOwners)) {
        $contentModuleAssignmentOwners = [];
    }
    $contentModuleAssignmentTotals = $contentModuleAssignments['totals'] ?? [];
    if (!is_array($contentModuleAssignmentTotals)) {
        $contentModuleAssignmentTotals = [];
    }

    $automationTriggerOptions = $data['automation_triggers'] ?? ['user_registered', 'post_published', 'feature_request_submitted', 'bug_report_created'];
    if (!is_array($automationTriggerOptions) || empty($automationTriggerOptions)) {
        $automationTriggerOptions = ['user_registered'];
    }
    $automationTriggerOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $automationTriggerOptions)));
    if (empty($automationTriggerOptions)) {
        $automationTriggerOptions = ['user_registered'];
    }
    $automationTriggerLabels = [];
    foreach ($automationTriggerOptions as $triggerValue) {
        $automationTriggerLabels[$triggerValue] = ucwords(str_replace('_', ' ', $triggerValue));
    }

    $automationActionTypes = $data['automation_action_types'] ?? ['enqueue_notification', 'record_activity', 'update_dataset'];
    if (!is_array($automationActionTypes) || empty($automationActionTypes)) {
        $automationActionTypes = ['enqueue_notification'];
    }
    $automationActionTypes = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $automationActionTypes)));
    if (empty($automationActionTypes)) {
        $automationActionTypes = ['enqueue_notification'];
    }

    $automationConditionTypes = $data['automation_condition_types'] ?? ['custom', 'role_equals', 'dataset_threshold', 'time_window'];
    if (!is_array($automationConditionTypes) || empty($automationConditionTypes)) {
        $automationConditionTypes = ['custom'];
    }
    $automationConditionTypes = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $automationConditionTypes)));
    if (empty($automationConditionTypes)) {
        $automationConditionTypes = ['custom'];
    }

    $automationPriorityOptions = $data['automation_priority_options'] ?? ['low', 'medium', 'high'];
    if (!is_array($automationPriorityOptions) || empty($automationPriorityOptions)) {
        $automationPriorityOptions = ['low', 'medium', 'high'];
    }
    $automationPriorityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $automationPriorityOptions)));
    if (empty($automationPriorityOptions)) {
        $automationPriorityOptions = ['medium'];
    }
    $automationPriorityLabels = [];
    foreach ($automationPriorityOptions as $priorityValue) {
        $automationPriorityLabels[$priorityValue] = ucwords(str_replace('_', ' ', $priorityValue));
    }

    $automationStatusClass = static function (string $value): string {
        $slug = strtolower($value);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        if ($slug === null) {
            $slug = '';
        }
        $slug = trim($slug, '-');
        return $slug === '' ? 'unknown' : $slug;
    };

    $automationDefaultOwnerRole = trim((string) ($data['automation_default_owner_role'] ?? 'admin'));
    if ($automationDefaultOwnerRole === '') {
        $automationDefaultOwnerRole = 'admin';
    }

    $formatAutomationLines = static function (array $entries) {
        $lines = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $type = strtolower(trim((string) ($entry['type'] ?? '')));
            if ($type === '') {
                $type = 'custom';
            }
            $options = $entry['options'] ?? [];
            $pairs = [];
            if (is_array($options)) {
                foreach ($options as $key => $value) {
                    if ($value === '' || $value === null) {
                        continue;
                    }
                    if (is_array($value)) {
                        $pairs[] = $key . '=' . json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    } else {
                        $pairs[] = $key . '=' . (string) $value;
                    }
                }
            }
            $line = $type;
            if (!empty($pairs)) {
                $line .= '|' . implode(', ', $pairs);
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    };

    $describeAutomationRule = static function (array $entry) {
        $type = strtolower(trim((string) ($entry['type'] ?? 'custom')));
        $label = ucwords(str_replace('_', ' ', $type));
        $options = $entry['options'] ?? [];
        if (!is_array($options) || empty($options)) {
            return $label;
        }
        $pairs = [];
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $pairs[] = htmlspecialchars($key) . ': ' . htmlspecialchars(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            } else {
                $pairs[] = htmlspecialchars((string) $key) . ': ' . htmlspecialchars((string) $value);
            }
        }
        return $label . ' (' . implode(', ', $pairs) . ')';
    };

    $automationStatusRank = [];
    foreach ($automationStatusOptions as $index => $value) {
        $automationStatusRank[$value] = $index;
    }

    $automationEntries = [];
    $automationStatusCounts = [];
    $automationTotalRuns = 0;
    $automationActiveCount = 0;

    foreach ($automationRecords as $record) {
        if (!is_array($record)) {
            continue;
        }

        $status = strtolower((string) ($record['status'] ?? $automationDefaultStatus));
        if (!in_array($status, $automationStatusOptions, true)) {
            $status = $automationDefaultStatus;
        }
        $automationStatusCounts[$status] = ($automationStatusCounts[$status] ?? 0) + 1;
        if ($status === 'enabled') {
            $automationActiveCount++;
        }

        $runCount = (int) ($record['run_count'] ?? 0);
        if ($runCount < 0) {
            $runCount = 0;
        }
        $automationTotalRuns += $runCount;

        $conditions = $record['conditions'] ?? [];
        if (!is_array($conditions)) {
            $conditions = [];
        }
        $actions = $record['actions'] ?? [];
        if (!is_array($actions)) {
            $actions = [];
        }

        $conditionsList = [];
        foreach ($conditions as $condition) {
            if (is_array($condition)) {
                $conditionsList[] = $describeAutomationRule($condition);
            }
        }

        $actionsList = [];
        foreach ($actions as $action) {
            if (is_array($action)) {
                $actionsList[] = $describeAutomationRule($action);
            }
        }

        $automationEntries[] = array_merge($record, [
            'status' => $status,
            'run_count' => $runCount,
            'conditions_lines' => $formatAutomationLines($conditions),
            'actions_lines' => $formatAutomationLines($actions),
            'conditions_list' => $conditionsList,
            'actions_list' => $actionsList,
        ]);
    }

    if (!empty($automationEntries)) {
        usort($automationEntries, static function (array $a, array $b) use ($automationStatusRank) {
            $rankA = $automationStatusRank[$a['status'] ?? ''] ?? PHP_INT_MAX;
            $rankB = $automationStatusRank[$b['status'] ?? ''] ?? PHP_INT_MAX;
            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            $timeA = strtotime((string) ($a['updated_at'] ?? $a['created_at'] ?? 'now'));
            $timeB = strtotime((string) ($b['updated_at'] ?? $b['created_at'] ?? 'now'));
            return $timeB <=> $timeA;
        });
    }
    $featureRequestStatusOptions = $data['feature_request_statuses'] ?? ['open', 'researching', 'planned', 'in_progress', 'completed', 'declined'];
    if (!is_array($featureRequestStatusOptions) || empty($featureRequestStatusOptions)) {
        $featureRequestStatusOptions = ['open'];
    }
    $featureRequestStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $featureRequestStatusOptions)));
    if (empty($featureRequestStatusOptions)) {
        $featureRequestStatusOptions = ['open'];
    }
    $featureRequestPriorityOptions = $data['feature_request_priorities'] ?? ['low', 'medium', 'high', 'critical'];
    if (!is_array($featureRequestPriorityOptions) || empty($featureRequestPriorityOptions)) {
        $featureRequestPriorityOptions = ['medium'];
    }
    $featureRequestPriorityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $featureRequestPriorityOptions)));
    if (empty($featureRequestPriorityOptions)) {
        $featureRequestPriorityOptions = ['medium'];
    }
    $featureRequestPolicy = (string) ($data['feature_request_policy'] ?? 'members');
    $featureRequestDefaultVisibility = strtolower((string) ($data['feature_request_default_visibility'] ?? 'members'));
    if (!in_array($featureRequestDefaultVisibility, ['public', 'members', 'private'], true)) {
        $featureRequestDefaultVisibility = 'members';
    }

    $bugStatusOptions = $data['bug_report_statuses'] ?? ['new', 'triaged', 'in_progress', 'resolved', 'wont_fix', 'duplicate'];
    if (!is_array($bugStatusOptions) || empty($bugStatusOptions)) {
        $bugStatusOptions = ['new'];
    }
    $bugStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $bugStatusOptions)));
    if (empty($bugStatusOptions)) {
        $bugStatusOptions = ['new'];
    }

    $bugSeverityOptions = $data['bug_report_severities'] ?? ['low', 'medium', 'high', 'critical'];
    if (!is_array($bugSeverityOptions) || empty($bugSeverityOptions)) {
        $bugSeverityOptions = ['medium'];
    }
    $bugSeverityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $bugSeverityOptions)));
    if (empty($bugSeverityOptions)) {
        $bugSeverityOptions = ['medium'];
    }

    $bugPolicySetting = (string) ($data['bug_report_policy'] ?? 'members');
    $bugDefaultVisibility = strtolower((string) ($data['bug_report_default_visibility'] ?? 'members'));
    if (!in_array($bugDefaultVisibility, ['public', 'members', 'private'], true)) {
        $bugDefaultVisibility = 'members';
    }

    $bugDefaultOwnerRole = trim((string) ($data['bug_report_default_owner_role'] ?? 'moderator'));
    if ($bugDefaultOwnerRole === '') {
        $bugDefaultOwnerRole = 'moderator';
    }

    $bugFeedDisplayLimit = (int) ($data['bug_report_feed_display_limit'] ?? 5);
    if ($bugFeedDisplayLimit < 1) {
        $bugFeedDisplayLimit = 5;
    }

    $bugStatusLabels = [];
    $bugStatusCounts = [];
    foreach ($bugStatusOptions as $statusOption) {
        $bugStatusLabels[$statusOption] = ucwords(str_replace('_', ' ', $statusOption));
        $bugStatusCounts[$statusOption] = 0;
    }

    $bugSeverityLabels = [];
    foreach ($bugSeverityOptions as $severityOption) {
        $bugSeverityLabels[$severityOption] = ucwords(str_replace('_', ' ', $severityOption));
    }

    $bugEntries = [];
    $bugTotalWatchers = 0;
    foreach ($bugReportRecords as $bug) {
        if (!is_array($bug)) {
            continue;
        }

        $status = strtolower((string) ($bug['status'] ?? $bugStatusOptions[0]));
        if (!isset($bugStatusLabels[$status])) {
            $bugStatusLabels[$status] = ucwords(str_replace('_', ' ', $status));
            $bugStatusCounts[$status] = 0;
        }
        $bugStatusCounts[$status] = ($bugStatusCounts[$status] ?? 0) + 1;

        $severity = strtolower((string) ($bug['severity'] ?? $bugSeverityOptions[0]));
        if (!isset($bugSeverityLabels[$severity])) {
            $bugSeverityLabels[$severity] = ucwords(str_replace('_', ' ', $severity));
        }

        $visibility = strtolower((string) ($bug['visibility'] ?? $bugDefaultVisibility));
        if (!in_array($visibility, ['public', 'members', 'private'], true)) {
            $visibility = $bugDefaultVisibility;
        }

        $watchers = $bug['watchers'] ?? [];
        if (!is_array($watchers)) {
            $watchers = [];
        }
        $watchers = array_values(array_unique(array_filter(array_map('intval', $watchers), static function ($value) {
            return $value > 0;
        })));
        $bugTotalWatchers += count($watchers);

        $tagsValue = '';
        if (!empty($bug['tags']) && is_array($bug['tags'])) {
            $tagsValue = implode(', ', array_map(static function ($tag) {
                return (string) $tag;
            }, $bug['tags']));
        }

        $stepsValue = '';
        if (!empty($bug['steps_to_reproduce']) && is_array($bug['steps_to_reproduce'])) {
            $stepsValue = implode("\n", array_map(static function ($step) {
                return (string) $step;
            }, $bug['steps_to_reproduce']));
        }

        $versionsValue = '';
        if (!empty($bug['affected_versions']) && is_array($bug['affected_versions'])) {
            $versionsValue = implode("\n", array_map(static function ($version) {
                return (string) $version;
            }, $bug['affected_versions']));
        }

        $linksValue = '';
        if (!empty($bug['reference_links']) && is_array($bug['reference_links'])) {
            $linksValue = implode("\n", array_map(static function ($link) {
                return (string) $link;
            }, $bug['reference_links']));
        }

        $attachmentsValue = '';
        if (!empty($bug['attachments']) && is_array($bug['attachments'])) {
            $attachmentsValue = implode("\n", array_map(static function ($attachment) {
                return (string) $attachment;
            }, $bug['attachments']));
        }

        $updatedAt = trim((string) ($bug['updated_at'] ?? $bug['created_at'] ?? ''));
        $updatedAtLabel = '';
        if ($updatedAt !== '') {
            $timestamp = strtotime($updatedAt);
            if ($timestamp !== false) {
                $updatedAtLabel = date('M j, Y H:i', $timestamp);
            }
        }

        $createdAt = trim((string) ($bug['created_at'] ?? ''));
        $createdAtLabel = '';
        if ($createdAt !== '') {
            $createdTimestamp = strtotime($createdAt);
            if ($createdTimestamp !== false) {
                $createdAtLabel = date('M j, Y H:i', $createdTimestamp);
            }
        }

        $lastActivity = trim((string) ($bug['last_activity_at'] ?? $updatedAt));
        $lastActivityLabel = '';
        if ($lastActivity !== '') {
            $activityTimestamp = strtotime($lastActivity);
            if ($activityTimestamp !== false) {
                $lastActivityLabel = date('M j, Y H:i', $activityTimestamp);
            }
        }

        $bugEntries[] = array_merge($bug, [
            'status' => $status,
            'severity' => $severity,
            'visibility' => $visibility,
            'watchers' => $watchers,
            'watchers_input' => implode("\n", array_map('strval', $watchers)),
            'tags_value' => $tagsValue,
            'steps_value' => $stepsValue,
            'versions_value' => $versionsValue,
            'links_value' => $linksValue,
            'attachments_value' => $attachmentsValue,
            'updated_at_label' => $updatedAtLabel,
            'created_at_label' => $createdAtLabel,
            'last_activity_label' => $lastActivityLabel,
        ]);
    }

    if (!empty($bugEntries)) {
        usort($bugEntries, static function (array $a, array $b) {
            $timeA = strtotime((string) ($a['last_activity_at'] ?? $a['updated_at'] ?? $a['created_at'] ?? 'now'));
            $timeB = strtotime((string) ($b['last_activity_at'] ?? $b['updated_at'] ?? $b['created_at'] ?? 'now'));
            return $timeB <=> $timeA;
        });
    }

    $eventStatusOptions = $data['event_status_options'] ?? ['draft', 'scheduled', 'completed', 'cancelled'];
    if (!is_array($eventStatusOptions) || empty($eventStatusOptions)) {
        $eventStatusOptions = ['draft', 'scheduled', 'completed', 'cancelled'];
    }
    $eventStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $eventStatusOptions)));
    if (empty($eventStatusOptions)) {
        $eventStatusOptions = ['draft'];
    }

    $eventStatusLabels = [];
    $eventStatusCounts = [];
    foreach ($eventStatusOptions as $statusValue) {
        $eventStatusLabels[$statusValue] = ucwords(str_replace('_', ' ', $statusValue));
        $eventStatusCounts[$statusValue] = 0;
    }

    $eventPolicySetting = (string) ($data['event_policy'] ?? 'moderators');
    if ($eventPolicySetting === 'enabled') {
        $eventPolicySetting = 'members';
    }
    if (!in_array($eventPolicySetting, ['disabled', 'members', 'moderators', 'admins'], true)) {
        $eventPolicySetting = 'moderators';
    }

    $eventDefaultVisibility = strtolower((string) ($data['event_default_visibility'] ?? 'members'));
    if (!in_array($eventDefaultVisibility, ['public', 'members', 'private'], true)) {
        $eventDefaultVisibility = 'members';
    }

    $eventVisibilityLabels = [
        'public' => 'Public',
        'members' => 'Members only',
        'private' => 'Administrators only',
    ];

    $eventRsvpPolicySetting = strtolower((string) ($data['event_rsvp_policy'] ?? 'members'));
    if (!in_array($eventRsvpPolicySetting, ['public', 'members', 'private'], true)) {
        $eventRsvpPolicySetting = 'members';
    }

    $eventDefaultTimezone = trim((string) ($data['event_default_timezone'] ?? 'UTC'));
    if ($eventDefaultTimezone === '') {
        $eventDefaultTimezone = 'UTC';
    }

    $eventEntries = [];
    $eventUpcomingCount = 0;
    $eventPastCount = 0;
    $eventTotalRsvps = 0;
    $eventTotalCapacity = 0;
    $nowTimestamp = time();

    foreach ($eventRecords as $eventRecord) {
        if (!is_array($eventRecord)) {
            continue;
        }

        $status = strtolower((string) ($eventRecord['status'] ?? $eventStatusOptions[0]));
        if (!isset($eventStatusLabels[$status])) {
            $eventStatusLabels[$status] = ucwords(str_replace('_', ' ', $status));
            $eventStatusCounts[$status] = 0;
        }
        $eventStatusCounts[$status] = ($eventStatusCounts[$status] ?? 0) + 1;

        $visibility = strtolower((string) ($eventRecord['visibility'] ?? $eventDefaultVisibility));
        if (!isset($eventVisibilityLabels[$visibility])) {
            $visibility = $eventDefaultVisibility;
        }

        $startAt = trim((string) ($eventRecord['start_at'] ?? ''));
        $endAt = trim((string) ($eventRecord['end_at'] ?? ''));
        $startTimestamp = $startAt !== '' ? strtotime($startAt) : false;
        $endTimestamp = $endAt !== '' ? strtotime($endAt) : false;
        if ($startTimestamp === false) {
            $startTimestamp = $nowTimestamp;
        }
        if ($endTimestamp === false) {
            $endTimestamp = $startTimestamp + 3600;
        }
        $isPast = $endTimestamp < $nowTimestamp;
        if ($isPast) {
            $eventPastCount++;
        } else {
            $eventUpcomingCount++;
        }

        $startInput = date('Y-m-d\TH:i', $startTimestamp);
        $endInput = date('Y-m-d\TH:i', $endTimestamp);
        $startLabel = date('M j, Y H:i', $startTimestamp);
        $endLabel = date('M j, Y H:i', $endTimestamp);

        $allowRsvp = !empty($eventRecord['allow_rsvp']);
        $rsvps = $eventRecord['rsvps'] ?? [];
        if (!is_array($rsvps)) {
            $rsvps = [];
        }
        $rsvps = array_values(array_unique(array_map('intval', $rsvps)));
        $eventTotalRsvps += count($rsvps);

        $rsvpLimit = $eventRecord['rsvp_limit'] ?? null;
        if ($rsvpLimit !== null) {
            $rsvpLimit = (int) $rsvpLimit;
            if ($rsvpLimit > 0) {
                $eventTotalCapacity += $rsvpLimit;
            }
        }

        $hosts = $eventRecord['hosts'] ?? [];
        if (!is_array($hosts)) {
            $hosts = [];
        }
        $hosts = array_values(array_unique(array_map('intval', $hosts)));
        $hostLabels = [];
        foreach ($hosts as $hostId) {
            $hostLabels[] = $userDirectory[$hostId] ?? ('User #' . $hostId);
        }

        $collaborators = $eventRecord['collaborators'] ?? [];
        if (!is_array($collaborators)) {
            $collaborators = [];
        }
        $collaborators = array_values(array_unique(array_map('intval', $collaborators)));
        $collaboratorLabels = [];
        foreach ($collaborators as $collaboratorId) {
            $collaboratorLabels[] = $userDirectory[$collaboratorId] ?? ('User #' . $collaboratorId);
        }

        $tags = $eventRecord['tags'] ?? [];
        if (!is_array($tags)) {
            $tags = [];
        }
        $tagsTextarea = implode("\n", $tags);

        $attachments = $eventRecord['attachments'] ?? [];
        if (!is_array($attachments)) {
            $attachments = [];
        }
        $attachmentsTextarea = implode("\n", $attachments);

        $hostsTextarea = implode("\n", array_map('strval', $hosts));
        $collaboratorsTextarea = implode("\n", array_map('strval', $collaborators));

        $eventEntries[] = array_merge($eventRecord, [
            'status' => $status,
            'visibility' => $visibility,
            'status_label' => $eventStatusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)),
            'visibility_label' => $eventVisibilityLabels[$visibility] ?? ucfirst($visibility),
            'start_timestamp' => $startTimestamp,
            'end_timestamp' => $endTimestamp,
            'start_input' => $startInput,
            'end_input' => $endInput,
            'start_label' => $startLabel,
            'end_label' => $endLabel,
            'hosts' => $hosts,
            'hosts_labels' => $hostLabels,
            'hosts_input' => $hostsTextarea,
            'collaborators' => $collaborators,
            'collaborators_labels' => $collaboratorLabels,
            'collaborators_input' => $collaboratorsTextarea,
            'tags_input' => $tagsTextarea,
            'attachments_input' => $attachmentsTextarea,
            'allow_rsvp' => $allowRsvp,
            'rsvps' => $rsvps,
            'rsvp_limit' => $rsvpLimit,
            'is_past' => $isPast,
        ]);
    }

    if (!empty($eventEntries)) {
        usort($eventEntries, static function (array $a, array $b) {
            $pastA = !empty($a['is_past']);
            $pastB = !empty($b['is_past']);
            if ($pastA !== $pastB) {
                return $pastA <=> $pastB;
            }

            $startA = (int) ($a['start_timestamp'] ?? 0);
            $startB = (int) ($b['start_timestamp'] ?? 0);

            if ($pastA && $pastB) {
                return $startB <=> $startA;
            }

            return $startA <=> $startB;
        });
    }

    $pollStatusOptions = $data['poll_statuses'] ?? ['draft', 'open', 'closed'];
    if (!is_array($pollStatusOptions) || empty($pollStatusOptions)) {
        $pollStatusOptions = ['draft', 'open', 'closed'];
    }
    $pollStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $pollStatusOptions)));
    if (empty($pollStatusOptions)) {
        $pollStatusOptions = ['draft', 'open', 'closed'];
    }

    $pollStatusLabels = [];
    $pollStatusCounts = [];
    foreach ($pollStatusOptions as $statusOption) {
        $pollStatusLabels[$statusOption] = ucwords(str_replace('_', ' ', $statusOption));
        $pollStatusCounts[$statusOption] = 0;
    }

    $pollPolicySetting = (string) ($data['poll_policy'] ?? 'moderators');
    $pollDefaultVisibility = strtolower((string) ($data['poll_default_visibility'] ?? 'members'));
    if (!in_array($pollDefaultVisibility, ['public', 'members', 'private'], true)) {
        $pollDefaultVisibility = 'members';
    }
    $pollAllowMultipleDefault = !empty($data['poll_allow_multiple_default']);

    $pollEntries = [];
    $pollTotalResponses = 0;
    foreach ($pollRecords as $pollRecord) {
        if (!is_array($pollRecord)) {
            continue;
        }
        $status = strtolower((string) ($pollRecord['status'] ?? $pollStatusOptions[0]));
        if (!isset($pollStatusLabels[$status])) {
            $pollStatusLabels[$status] = ucwords(str_replace('_', ' ', $status));
            $pollStatusCounts[$status] = 0;
        }
        $pollStatusCounts[$status] = ($pollStatusCounts[$status] ?? 0) + 1;

        $options = $pollRecord['options'] ?? [];
        if (!is_array($options)) {
            $options = [];
        }
        $normalizedOptions = [];
        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }
            $label = trim((string) ($option['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $voteCount = (int) ($option['vote_count'] ?? 0);
            if ($voteCount < 0) {
                $voteCount = 0;
            }
            $supporters = $option['supporters'] ?? [];
            if (!is_array($supporters)) {
                $supporters = [];
            }
            $supporters = array_values(array_unique(array_filter(array_map('intval', $supporters), static function ($value) {
                return $value > 0;
            })));
            $normalizedOptions[] = [
                'id' => (int) ($option['id'] ?? 0),
                'label' => $label,
                'vote_count' => $voteCount,
                'supporters' => $supporters,
                'supporter_count' => count($supporters),
            ];
        }
        if (!empty($normalizedOptions)) {
            usort($normalizedOptions, static function (array $a, array $b) {
                return ($b['vote_count'] ?? 0) <=> ($a['vote_count'] ?? 0);
            });
        }
        $totalResponses = (int) ($pollRecord['total_responses'] ?? 0);
        if ($totalResponses < 0) {
            $totalResponses = 0;
        }
        $totalVotes = (int) ($pollRecord['total_votes'] ?? 0);
        if ($totalVotes < 0) {
            $totalVotes = 0;
        }
        $pollTotalResponses += $totalResponses;

        $entry = $pollRecord;
        $entry['status'] = $status;
        $entry['options'] = $normalizedOptions;
        $entry['total_responses'] = $totalResponses;
        $entry['total_votes'] = $totalVotes;
        $entry['allow_multiple'] = !empty($pollRecord['allow_multiple']);
        $entry['max_selections'] = (int) ($pollRecord['max_selections'] ?? ($entry['allow_multiple'] ? 0 : 1));
        $pollEntries[] = $entry;
    }

    if (!empty($pollEntries)) {
        usort($pollEntries, static function (array $a, array $b) {
            $timeA = strtotime((string) ($a['updated_at'] ?? $a['created_at'] ?? 'now'));
            $timeB = strtotime((string) ($b['updated_at'] ?? $b['created_at'] ?? 'now'));
            return $timeB <=> $timeA;
        });
    }

    $knowledgeDefaultStatus = strtolower((string) ($data['knowledge_default_status'] ?? 'published'));
    if (!in_array($knowledgeDefaultStatus, ['draft', 'scheduled', 'published', 'archived'], true)) {
        $knowledgeDefaultStatus = 'published';
    }
    $knowledgeDefaultVisibility = strtolower((string) ($data['knowledge_default_visibility'] ?? 'public'));
    if (!in_array($knowledgeDefaultVisibility, ['public', 'members', 'private'], true)) {
        $knowledgeDefaultVisibility = 'public';
    }
    $knowledgeDefaultCategory = $data['knowledge_default_category'] ?? null;
    if ($knowledgeDefaultCategory !== null) {
        $knowledgeDefaultCategory = (int) $knowledgeDefaultCategory;
        if ($knowledgeDefaultCategory <= 0) {
            $knowledgeDefaultCategory = null;
        }
    }
    $message = $data['message'] ?? '';
    $errors = $data['errors'] ?? [];
    $activityRecords = $data['activity_records'] ?? [];
    $activityFilters = $data['activity_filters'] ?? ['dataset' => '', 'category' => '', 'action' => '', 'user' => ''];
    $activityLimit = (int) ($data['activity_limit'] ?? 50);
    $activityTotal = (int) ($data['activity_total'] ?? count($activityRecords));
    $activityDatasetLabels = $data['activity_dataset_labels'] ?? [];
    $activityCategories = $data['activity_categories'] ?? [];
    $activityActions = $data['activity_actions'] ?? [];

    $userIndex = [];
    foreach ($users as $user) {
        $userIndex[(string) ($user['id'] ?? '')] = $user;
    }

    $body = '<section class="setup-intro">';
    $body .= '<h1>Asset Setup</h1>';
    $body .= '<p>Configure default parameters, permissions, and overrides for every asset without editing files directly.</p>';
    if ($message !== '') {
        $body .= '<div class="notice success">' . htmlspecialchars($message) . '</div>';
    }
    if (!empty($errors)) {
        $body .= '<div class="notice error"><ul>';
        foreach ($errors as $error) {
            $body .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $body .= '</ul></div>';
    }
    $body .= '</section>';

    $body .= '<div class="asset-setup-grid">';

    foreach ($configurations as $asset => $configuration) {
        $parameters = $configuration['parameters'] ?? [];
        $allowedRoles = $configuration['allowed_roles'] ?? [];
        $allowUserOverride = !empty($configuration['allow_user_override']);
        $mirrorOf = $configuration['mirror_of'] ?? null;
        $isMirror = is_string($mirrorOf) && $mirrorOf !== '';

        $articleAttributes = 'class="asset-card" data-asset="' . htmlspecialchars($asset) . '"';
        if ($isMirror) {
            $articleAttributes .= ' data-mirror="true"';
        }

        $body .= '<article ' . $articleAttributes . '>';
        $body .= '<header><h2>' . htmlspecialchars($configuration['label'] ?? $asset) . '</h2>';
        $body .= '<p class="asset-meta">' . htmlspecialchars($asset) . ' · Scope: ' . htmlspecialchars($configuration['scope'] ?? 'global') . ' · Extension: ' . htmlspecialchars($configuration['extension'] ?? '') . '</p>';
        if ($isMirror) {
            $body .= '<p class="asset-meta-note">Mirrors <code>' . htmlspecialchars($mirrorOf) . '</code> and stays synchronised automatically.</p>';
        }
        $body .= '</header>';

        $body .= '<section class="asset-section">';
        $body .= '<h3>Defaults &amp; Permissions</h3>';
        $body .= '<form method="post" action="/setup.php" class="asset-form">';
        $body .= '<input type="hidden" name="action" value="update_defaults">';
        $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($asset) . '">';
        $body .= '<div class="field-grid roadmap-basic-grid">';

        foreach ($parameters as $key => $definition) {
            $label = $definition['label'] ?? $key;
            $type = $definition['type'] ?? 'text';
            $defaultValue = $definition['default'] ?? '';
            $description = $definition['description'] ?? '';

            $body .= '<label class="field">';
            $body .= '<span class="field-label">' . htmlspecialchars($label) . '</span>';
            if ($type === 'boolean') {
                $checked = $defaultValue ? ' checked' : '';
                $body .= '<span class="field-control"><input type="checkbox" name="defaults[' . htmlspecialchars($key) . ']" value="1"' . $checked . '></span>';
            } elseif ($type === 'select') {
                $body .= '<span class="field-control"><select name="defaults[' . htmlspecialchars($key) . ']">';
                $options = $definition['options'] ?? [];
                foreach ($options as $option) {
                    $selected = ((string) $defaultValue === (string) $option) ? ' selected' : '';
                    $body .= '<option value="' . htmlspecialchars((string) $option) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $option)) . '</option>';
                }
                $body .= '</select></span>';
            } else {
                $body .= '<span class="field-control"><input type="text" name="defaults[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars((string) $defaultValue) . '"></span>';
            }
            if ($description !== '') {
                $body .= '<span class="field-description">' . htmlspecialchars($description) . '</span>';
            }
            $body .= '</label>';
        }

        $body .= '</div>';
        $body .= '<fieldset class="field-group">';
        $body .= '<legend>Roles allowed to manage overrides</legend>';
        $body .= '<div class="field-checkbox-group">';
        foreach ($roles as $role => $roleDescription) {
            $checked = in_array($role, $allowedRoles, true) ? ' checked' : '';
            $body .= '<label><input type="checkbox" name="allowed_roles[]" value="' . htmlspecialchars($role) . '"' . $checked . ($isMirror ? ' disabled' : '') . '> ' . htmlspecialchars(ucfirst($role)) . '</label>';
        }
        $body .= '</div>';
        $checkedOverride = $allowUserOverride ? ' checked' : '';
        $body .= '<label class="field-toggle"><input type="checkbox" name="allow_user_override" value="1"' . $checkedOverride . ($isMirror ? ' disabled' : '') . '> Allow members to personalise this asset</label>';
        $body .= '</fieldset>';
        $body .= '<button type="submit" class="button primary">Save defaults</button>';
        $body .= '</form>';
        $body .= '</section>';

        if ($isMirror) {
            $body .= '<section class="asset-section">';
            $body .= '<h3>Overrides</h3>';
            $body .= '<p class="asset-note">Configuration and overrides are inherited from <code>' . htmlspecialchars($mirrorOf) . '</code>. Adjust the source asset to change delivery.</p>';
            $body .= '</section>';
        } else {
                $body .= '<section class="asset-section">';
                $body .= '<h3>Global override</h3>';
                $globalValues = $overrides['global'][$asset] ?? [];
                $body .= '<form method="post" action="/setup.php" class="asset-form">';
                $body .= '<input type="hidden" name="action" value="update_override">';
                $body .= '<input type="hidden" name="scope" value="global">';
                $body .= '<input type="hidden" name="identifier" value="global">';
                $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($asset) . '">';
                $body .= '<div class="field-grid">';
                foreach ($parameters as $key => $definition) {
                    $label = $definition['label'] ?? $key;
                    $type = $definition['type'] ?? 'text';
                    $value = $globalValues[$key] ?? ($definition['default'] ?? '');
                    $description = $definition['description'] ?? '';

                    $body .= '<label class="field">';
                    $body .= '<span class="field-label">' . htmlspecialchars($label) . '</span>';
                    if ($type === 'boolean') {
                        $checked = $value ? ' checked' : '';
                        $body .= '<span class="field-control"><input type="checkbox" name="override[' . htmlspecialchars($key) . ']" value="1"' . $checked . '></span>';
                    } elseif ($type === 'select') {
                        $body .= '<span class="field-control"><select name="override[' . htmlspecialchars($key) . ']">';
                        $options = $definition['options'] ?? [];
                        foreach ($options as $option) {
                            $selected = ((string) $value === (string) $option) ? ' selected' : '';
                            $body .= '<option value="' . htmlspecialchars((string) $option) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $option)) . '</option>';
                        }
                        $body .= '</select></span>';
                    } else {
                        $body .= '<span class="field-control"><input type="text" name="override[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars((string) $value) . '"></span>';
                    }
                    if ($description !== '') {
                        $body .= '<span class="field-description">' . htmlspecialchars($description) . '</span>';
                    }
                    $body .= '</label>';
                }
                $body .= '</div>';
                $body .= '<div class="action-row">';
                $body .= '<button type="submit" class="button">Save override</button>';
                $body .= '</div>';
                $body .= '</form>';
                if (!empty($globalValues)) {
                    $body .= '<form method="post" action="/setup.php" class="asset-form inline">';
                    $body .= '<input type="hidden" name="action" value="clear_override">';
                    $body .= '<input type="hidden" name="scope" value="global">';
                    $body .= '<input type="hidden" name="identifier" value="global">';
                    $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($asset) . '">';
                    $body .= '<button type="submit" class="button danger">Remove global override</button>';
                    $body .= '</form>';
                }
                $body .= '</section>';

                $body .= '<section class="asset-section">';
                $body .= '<h3>Role overrides</h3>';
                foreach ($roles as $role => $roleDescription) {
                    $roleValues = $overrides['roles'][$role][$asset] ?? [];
                    $body .= '<form method="post" action="/setup.php" class="asset-form role-form">';
                    $body .= '<input type="hidden" name="action" value="update_override">';
                    $body .= '<input type="hidden" name="scope" value="roles">';
                    $body .= '<input type="hidden" name="identifier" value="' . htmlspecialchars($role) . '">';
                    $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($asset) . '">';
                    $body .= '<fieldset>';
                    $body .= '<legend>' . htmlspecialchars(ucfirst($role)) . '</legend>';
                    $body .= '<div class="field-grid">';
                    foreach ($parameters as $key => $definition) {
                        $label = $definition['label'] ?? $key;
                        $type = $definition['type'] ?? 'text';
                        $value = $roleValues[$key] ?? ($definition['default'] ?? '');

                        $body .= '<label class="field">';
                        $body .= '<span class="field-label">' . htmlspecialchars($label) . '</span>';
                        if ($type === 'boolean') {
                            $checked = $value ? ' checked' : '';
                            $body .= '<span class="field-control"><input type="checkbox" name="override[' . htmlspecialchars($key) . ']" value="1"' . $checked . '></span>';
                        } elseif ($type === 'select') {
                            $body .= '<span class="field-control"><select name="override[' . htmlspecialchars($key) . ']">';
                            $options = $definition['options'] ?? [];
                            foreach ($options as $option) {
                                $selected = ((string) $value === (string) $option) ? ' selected' : '';
                                $body .= '<option value="' . htmlspecialchars((string) $option) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $option)) . '</option>';
                            }
                            $body .= '</select></span>';
                        } else {
                            $body .= '<span class="field-control"><input type="text" name="override[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars((string) $value) . '"></span>';
                        }
                        $body .= '</label>';
                    }
                    $body .= '</div>';
                    $body .= '<div class="action-row">';
                    $body .= '<button type="submit" class="button">Save role override</button>';
                    if (!empty($roleValues)) {
                        $body .= '<button type="submit" name="action_override" value="clear_override" class="button danger">Clear</button>';
                    }
                    $body .= '</div>';
                    $body .= '</fieldset>';
                    $body .= '</form>';
                }
                $body .= '</section>';

                $body .= '<section class="asset-section">';
                $body .= '<h3>User overrides</h3>';
                $userOverrides = $overrides['users'] ?? [];
                foreach ($userOverrides as $userId => $assetsOverrides) {
                    if (!isset($assetsOverrides[$asset])) {
                        continue;
                    }
                    $values = $assetsOverrides[$asset];
                    $user = $userIndex[(string) $userId] ?? ['display_name' => 'User #' . $userId];
                    $displayName = $user['display_name'] ?? ($user['username'] ?? ('User #' . $userId));
                    $body .= '<form method="post" action="/setup.php" class="asset-form user-form">';
                    $body .= '<input type="hidden" name="action" value="update_override">';
                    $body .= '<input type="hidden" name="scope" value="users">';
                    $body .= '<input type="hidden" name="identifier" value="' . htmlspecialchars((string) $userId) . '">';
                    $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($asset) . '">';
                    $body .= '<fieldset>';
                    $body .= '<legend>' . htmlspecialchars($displayName) . '</legend>';
                    $body .= '<div class="field-grid">';
                    foreach ($parameters as $key => $definition) {
                        $label = $definition['label'] ?? $key;
                        $type = $definition['type'] ?? 'text';
                        $value = $values[$key] ?? ($definition['default'] ?? '');
                        $body .= '<label class="field">';
                        $body .= '<span class="field-label">' . htmlspecialchars($label) . '</span>';
                        if ($type === 'boolean') {
                            $checked = $value ? ' checked' : '';
                            $body .= '<span class="field-control"><input type="checkbox" name="override[' . htmlspecialchars($key) . ']" value="1"' . $checked . '></span>';
                        } elseif ($type === 'select') {
                            $body .= '<span class="field-control"><select name="override[' . htmlspecialchars($key) . ']">';
                            $options = $definition['options'] ?? [];
                            foreach ($options as $option) {
                                $selected = ((string) $value === (string) $option) ? ' selected' : '';
                                $body .= '<option value="' . htmlspecialchars((string) $option) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $option)) . '</option>';
                            }
                            $body .= '</select></span>';
                        } else {
                            $body .= '<span class="field-control"><input type="text" name="override[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars((string) $value) . '"></span>';
                        }
                        $body .= '</label>';
                    }
                    $body .= '</div>';
                    $body .= '<div class="action-row">';
                    $body .= '<button type="submit" class="button">Save user override</button>';
                    $body .= '<button type="submit" name="action_override" value="clear_override" class="button danger">Remove override</button>';
                    $body .= '</div>';
                    $body .= '</fieldset>';
                    $body .= '</form>';
                }

                if (!empty($users)) {
                    $body .= '<form method="post" action="/setup.php" class="asset-form user-form">';
                    $body .= '<input type="hidden" name="action" value="update_override">';
                    $body .= '<input type="hidden" name="scope" value="users">';
                    $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($asset) . '">';
                    $body .= '<fieldset>';
                    $body .= '<legend>Create or update user override</legend>';
                    $body .= '<label class="field">';
                    $body .= '<span class="field-label">Select user</span>';
                    $body .= '<span class="field-control"><select name="identifier">';
                    foreach ($users as $user) {
                        $id = (string) ($user['id'] ?? '');
                        $displayName = $user['display_name'] ?? ($user['username'] ?? $id);
                        $body .= '<option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($displayName) . '</option>';
                    }
                    $body .= '</select></span>';
                    $body .= '</label>';
                    $body .= '<div class="field-grid">';
                    foreach ($parameters as $key => $definition) {
                        $label = $definition['label'] ?? $key;
                        $type = $definition['type'] ?? 'text';
                        $value = $definition['default'] ?? '';
                        $body .= '<label class="field">';
                        $body .= '<span class="field-label">' . htmlspecialchars($label) . '</span>';
                        if ($type === 'boolean') {
                            $body .= '<span class="field-control"><input type="checkbox" name="override[' . htmlspecialchars($key) . ']" value="1"></span>';
                        } elseif ($type === 'select') {
                            $body .= '<span class="field-control"><select name="override[' . htmlspecialchars($key) . ']">';
                            $options = $definition['options'] ?? [];
                            foreach ($options as $option) {
                                $selected = ((string) $value === (string) $option) ? ' selected' : '';
                                $body .= '<option value="' . htmlspecialchars((string) $option) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $option)) . '</option>';
                            }
                            $body .= '</select></span>';
                        } else {
                            $body .= '<span class="field-control"><input type="text" name="override[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars((string) $value) . '"></span>';
                        }
                        $body .= '</label>';
                    }
                    $body .= '</div>';
                    $body .= '<div class="action-row">';
                    $body .= '<button type="submit" class="button">Save user override</button>';
                    $body .= '</div>';
                    $body .= '</fieldset>';
                    $body .= '</form>';
                }

                $body .= '</section>';

        }

        $body .= '</article>';
    }

    $body .= '</div>';

    $datasetOptions = [];
    foreach ($datasets as $datasetKey => $definition) {
        $datasetOptions[$datasetKey] = $definition['label'] ?? $datasetKey;
    }

    $body .= '<section class="content-module-manager">';
    $body .= '<h2>Content Modules</h2>';
    $body .= '<p>Design interdependent content types that reuse shared categories, field prompts, and styling tokens pulled from the XML blueprints.</p>';

    if (!empty($contentModuleUsageModules)) {
        $trackedModules = (int) ($contentModuleUsageTotals['modules'] ?? count($contentModuleUsageModules));
        $trackedPosts = (int) ($contentModuleUsageTotals['posts'] ?? 0);
        $completedPosts = (int) ($contentModuleUsageTotals['posts_completed'] ?? 0);
        $overduePosts = (int) ($contentModuleUsageTotals['posts_overdue'] ?? 0);
        $dueSoonPosts = (int) ($contentModuleUsageTotals['posts_due_soon'] ?? 0);
        $totalTasks = (int) ($contentModuleUsageTotals['tasks'] ?? 0);
        $completedTasks = (int) ($contentModuleUsageTotals['tasks_completed'] ?? 0);
        $pendingTasks = (int) ($contentModuleUsageTotals['tasks_pending'] ?? max(0, $totalTasks - $completedTasks));

        $completionAccumulator = 0.0;
        $completionModules = 0;
        foreach ($contentModuleUsageModules as $usageEntry) {
            if (!is_array($usageEntry)) {
                continue;
            }
            $completionAccumulator += (float) ($usageEntry['percent_average'] ?? 0.0);
            $completionModules++;
        }
        $averageCompletion = $completionModules > 0 ? $completionAccumulator / $completionModules : 0.0;
        $averageCompletion = max(0.0, min(100.0, $averageCompletion));
        $averageCompletionLabel = rtrim(rtrim(sprintf('%.1f', $averageCompletion), '0'), '.');
        if ($averageCompletionLabel === '') {
            $averageCompletionLabel = '0';
        }
        $averageCompletionLabel .= '%';

        $attentionModules = array_values(array_filter($contentModuleUsageModules, static function ($entry) {
            return is_array($entry) && in_array($entry['attention_state'] ?? '', ['overdue', 'due_soon'], true);
        }));

        $body .= '<div class="content-module-usage-summary">';
        $body .= '<h3>Checklist coverage</h3>';
        $body .= '<p class="content-module-usage-intro">Track module adoption and outstanding work without leaving the setup dashboard.</p>';
        $body .= '<ul class="content-module-usage-metrics">';
        $body .= '<li><span class="metric-value">' . htmlspecialchars(number_format($trackedModules)) . '</span><span class="metric-label">Modules tracked</span></li>';
        $body .= '<li><span class="metric-value">' . htmlspecialchars(number_format($trackedPosts)) . '</span><span class="metric-label">Guided posts</span><span class="metric-detail">' . htmlspecialchars(number_format($completedPosts)) . ' complete</span></li>';
        $body .= '<li><span class="metric-value">' . htmlspecialchars($averageCompletionLabel) . '</span><span class="metric-label">Average completion</span></li>';
        if ($totalTasks > 0) {
            $body .= '<li><span class="metric-value">' . htmlspecialchars(number_format($completedTasks)) . ' / ' . htmlspecialchars(number_format($totalTasks)) . '</span><span class="metric-label">Checklist items checked off</span></li>';
        }
        if ($overduePosts > 0 || $dueSoonPosts > 0) {
            $attentionLabelParts = [];
            if ($overduePosts > 0) {
                $attentionLabelParts[] = htmlspecialchars(number_format($overduePosts)) . ' overdue';
            }
            if ($dueSoonPosts > 0) {
                $attentionLabelParts[] = htmlspecialchars(number_format($dueSoonPosts)) . ' due soon';
            }
            $body .= '<li><span class="metric-value warning">' . implode(' · ', $attentionLabelParts) . '</span><span class="metric-label">Needs attention</span></li>';
        } elseif ($pendingTasks > 0) {
            $body .= '<li><span class="metric-value">' . htmlspecialchars(number_format($pendingTasks)) . '</span><span class="metric-label">Open checklist items</span></li>';
        }
        $body .= '</ul>';

        if (!empty($attentionModules)) {
            $body .= '<div class="content-module-usage-alerts">';
            $body .= '<h4>Follow-up priorities</h4>';
            $body .= '<ul>';
            $attentionDisplay = array_slice($attentionModules, 0, 5);
            foreach ($attentionDisplay as $usageEntry) {
                if (!is_array($usageEntry)) {
                    continue;
                }
                $stateClass = 'state-' . htmlspecialchars($usageEntry['attention_state'] ?? 'idle');
                $body .= '<li class="content-module-usage-alert ' . $stateClass . '">';
                $body .= '<strong>' . htmlspecialchars($usageEntry['label'] ?? $usageEntry['key'] ?? 'Module') . '</strong>';
                $body .= '<span class="attention-status">' . htmlspecialchars($usageEntry['attention_label'] ?? '') . '</span>';
                if (!empty($usageEntry['last_activity_display'])) {
                    $body .= '<span class="attention-meta">Updated ' . htmlspecialchars($usageEntry['last_activity_display']) . '</span>';
                }
                $body .= '</li>';
            }
            if (count($attentionModules) > count($attentionDisplay)) {
                $remaining = count($attentionModules) - count($attentionDisplay);
                $body .= '<li class="content-module-usage-alert more">+' . htmlspecialchars(number_format($remaining)) . ' more module' . ($remaining === 1 ? '' : 's') . ' need attention</li>';
            }
            $body .= '</ul>';
            $body .= '</div>';
        }
        $body .= '</div>';
        if (!empty($contentModuleAssignmentOwners)) {
            $openTotal = (int) ($contentModuleAssignmentTotals['tasks_pending'] ?? 0);
            $dueSoonTotal = (int) ($contentModuleAssignmentTotals['tasks_due_soon'] ?? 0);
            $overdueTotal = (int) ($contentModuleAssignmentTotals['tasks_overdue'] ?? 0);
            $completedTotal = (int) ($contentModuleAssignmentTotals['tasks_completed'] ?? 0);
            $body .= '<div class="content-module-ownership">';
            $body .= '<h4>Task ownership</h4>';
            $summaryBits = [];
            $summaryBits[] = htmlspecialchars(number_format($openTotal)) . ' open';
            if ($overdueTotal > 0) {
                $summaryBits[] = htmlspecialchars(number_format($overdueTotal)) . ' overdue';
            }
            if ($dueSoonTotal > 0) {
                $summaryBits[] = htmlspecialchars(number_format($dueSoonTotal)) . ' due soon';
            }
            if ($completedTotal > 0) {
                $summaryBits[] = htmlspecialchars(number_format($completedTotal)) . ' complete';
            }
            $body .= '<p class="ownership-summary">' . implode(' · ', $summaryBits) . '</p>';

            $ownerPreview = array_slice($contentModuleAssignmentOwners, 0, 6);
            $body .= '<ul class="content-module-ownership-list">';
            foreach ($ownerPreview as $ownerEntry) {
                if (!is_array($ownerEntry)) {
                    continue;
                }
                $ownerLabel = trim((string) ($ownerEntry['label'] ?? $ownerEntry['key'] ?? 'Owner'));
                $pendingCount = (int) ($ownerEntry['tasks_pending'] ?? 0);
                $dueSoonCount = (int) ($ownerEntry['tasks_due_soon'] ?? 0);
                $overdueCount = (int) ($ownerEntry['tasks_overdue'] ?? 0);
                $completedCount = (int) ($ownerEntry['tasks_completed'] ?? 0);
                $moduleCount = (int) ($ownerEntry['module_count'] ?? 0);
                $postCount = (int) ($ownerEntry['post_count'] ?? 0);
                $earliestDue = trim((string) ($ownerEntry['earliest_due_display'] ?? ''));
                $stateClass = 'state-ok';
                if ($overdueCount > 0) {
                    $stateClass = 'state-overdue';
                } elseif ($dueSoonCount > 0) {
                    $stateClass = 'state-due-soon';
                } elseif ($pendingCount > 0) {
                    $stateClass = 'state-pending';
                }
                $body .= '<li class="ownership-item ' . $stateClass . '">';
                $body .= '<header><strong>' . htmlspecialchars($ownerLabel) . '</strong>';
                $body .= '<span class="ownership-count">' . htmlspecialchars(number_format($pendingCount)) . ' open</span>';
                if ($completedCount > 0) {
                    $body .= '<span class="ownership-count complete">' . htmlspecialchars(number_format($completedCount)) . ' complete</span>';
                }
                $body .= '</header>';
                $metaBits = [];
                if ($moduleCount > 0) {
                    $metaBits[] = htmlspecialchars(number_format($moduleCount)) . ' module' . ($moduleCount === 1 ? '' : 's');
                }
                if ($postCount > 0) {
                    $metaBits[] = htmlspecialchars(number_format($postCount)) . ' post' . ($postCount === 1 ? '' : 's');
                }
                if ($earliestDue !== '') {
                    $metaBits[] = 'Next due ' . htmlspecialchars($earliestDue);
                }
                if (!empty($metaBits)) {
                    $body .= '<p class="ownership-meta">' . implode(' · ', $metaBits) . '</p>';
                }
                $attentionTasks = $ownerEntry['attention_tasks'] ?? [];
                if (!is_array($attentionTasks)) {
                    $attentionTasks = [];
                }
                if (!empty($attentionTasks)) {
                    $body .= '<ul class="ownership-task-list">';
                    $attentionPreview = array_slice($attentionTasks, 0, 3);
                    foreach ($attentionPreview as $task) {
                        if (!is_array($task)) {
                            continue;
                        }
                        $taskLabel = trim((string) ($task['label'] ?? 'Checklist task'));
                        $taskState = 'state-' . htmlspecialchars($task['state'] ?? 'pending');
                        $taskDue = trim((string) ($task['due_display'] ?? ''));
                        $taskModule = trim((string) ($task['module_label'] ?? $task['module_key'] ?? 'Module'));
                        $body .= '<li class="ownership-task ' . $taskState . '"><span class="ownership-task-label">' . htmlspecialchars($taskLabel) . '</span>';
                        if ($taskModule !== '') {
                            $body .= '<span class="ownership-task-module">' . htmlspecialchars($taskModule) . '</span>';
                        }
                        if ($taskDue !== '') {
                            $body .= '<span class="ownership-task-due">Due ' . htmlspecialchars($taskDue) . '</span>';
                        }
                        $body .= '</li>';
                    }
                    if (count($attentionTasks) > count($attentionPreview)) {
                        $remaining = count($attentionTasks) - count($attentionPreview);
                        $body .= '<li class="ownership-task more">+' . htmlspecialchars(number_format($remaining)) . ' more task' . ($remaining === 1 ? '' : 's') . '</li>';
                    }
                    $body .= '</ul>';
                }
                $body .= '</li>';
            }
            if (count($contentModuleAssignmentOwners) > count($ownerPreview)) {
                $remainingOwners = count($contentModuleAssignmentOwners) - count($ownerPreview);
                $body .= '<li class="ownership-item more">+' . htmlspecialchars(number_format($remainingOwners)) . ' more owner' . ($remainingOwners === 1 ? '' : 's') . '</li>';
            }
            $body .= '</ul>';
            $body .= '</div>';
        }
    }

    if (empty($contentModuleRecords)) {
        $body .= '<p class="notice info">No content modules are active yet. Import a blueprint or create one from scratch below.</p>';
    }

    foreach ($contentModuleRecords as $module) {
        $moduleId = (int) ($module['id'] ?? 0);
        $normalizedModule = fg_normalize_content_module_definition($module);
        $moduleLabel = trim((string) ($module['label'] ?? 'Content module'));
        $moduleKey = (string) ($module['key'] ?? '');
        $moduleDataset = (string) ($module['dataset'] ?? 'posts');
        $moduleFormat = trim((string) ($module['format'] ?? ''));
        $moduleDescription = trim((string) ($module['description'] ?? ''));
        $moduleCategories = array_map('strval', $module['categories'] ?? []);
        $moduleFields = $module['fields'] ?? [];
        $moduleProfilePrompts = $module['profile_prompts'] ?? [];
        $moduleWizardSteps = $module['wizard_steps'] ?? [];
        $moduleCssTokens = $module['css_tokens'] ?? [];
        $moduleGuides = $module['guides'] ?? [];
        $moduleStatus = strtolower((string) ($module['status'] ?? 'active'));
        if (!in_array($moduleStatus, ['active', 'draft', 'archived'], true)) {
            $moduleStatus = 'active';
        }
        $moduleVisibility = strtolower((string) ($module['visibility'] ?? 'members'));
        if (!in_array($moduleVisibility, ['everyone', 'members', 'admins'], true)) {
            $moduleVisibility = 'members';
        }
        $moduleAllowedRaw = $module['allowed_roles'] ?? [];
        if (is_string($moduleAllowedRaw)) {
            $moduleAllowedRaw = preg_split('/\R+/u', $moduleAllowedRaw) ?: [];
        }
        if (!is_array($moduleAllowedRaw)) {
            $moduleAllowedRaw = [];
        }
        $moduleAllowedRoles = array_values(array_unique(array_filter(array_map(static function ($role) {
            return strtolower(trim((string) $role));
        }, $moduleAllowedRaw), static function ($role) {
            return $role !== '';
        })));

        $categoryText = htmlspecialchars(implode("\n", $moduleCategories));

        $fieldLines = [];
        foreach ($moduleFields as $field) {
            if (is_array($field)) {
                $label = trim((string) ($field['label'] ?? ''));
                $description = trim((string) ($field['description'] ?? ''));
                $fieldLines[] = htmlspecialchars($description === '' ? $label : $label . '|' . $description);
            } else {
                $fieldLines[] = htmlspecialchars((string) $field);
            }
        }

        $profileLines = [];
        foreach ($moduleProfilePrompts as $prompt) {
            if (is_array($prompt)) {
                $label = trim((string) ($prompt['label'] ?? ''));
                $description = trim((string) ($prompt['description'] ?? ''));
                $profileLines[] = htmlspecialchars($description === '' ? $label : $label . '|' . $description);
            } else {
                $profileLines[] = htmlspecialchars((string) $prompt);
            }
        }

        $wizardLines = [];
        foreach ($moduleWizardSteps as $step) {
            if (is_array($step)) {
                $title = trim((string) ($step['title'] ?? ''));
                $prompt = trim((string) ($step['prompt'] ?? ''));
                $wizardLines[] = htmlspecialchars($prompt === '' ? $title : $title . '|' . $prompt);
            } else {
                $wizardLines[] = htmlspecialchars((string) $step);
            }
        }

        $taskLines = [];
        $normalizedTasks = $normalizedModule['tasks'] ?? [];
        if (!is_array($normalizedTasks)) {
            $normalizedTasks = [];
        }
        $taskProgress = $normalizedModule['task_progress'] ?? fg_content_module_task_progress($normalizedTasks);
        foreach ($normalizedTasks as $task) {
            if (!is_array($task)) {
                continue;
            }
            $label = trim((string) ($task['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $description = trim((string) ($task['description'] ?? ''));
            $completed = !empty($task['completed']);
            $owner = trim((string) ($task['owner'] ?? ''));
            $dueInput = trim((string) ($task['due_date'] ?? ($task['due_display'] ?? '')));
            $priority = trim((string) ($task['priority'] ?? ''));
            $notes = trim((string) ($task['notes'] ?? ''));

            $parts = [
                $label,
                $description,
                $completed ? 'complete' : '',
                $owner,
                $dueInput,
                $priority,
                $notes,
            ];
            while (!empty($parts) && end($parts) === '') {
                array_pop($parts);
            }
            $taskLines[] = htmlspecialchars(implode('|', $parts));
        }

        if (!is_array($moduleGuides)) {
            $moduleGuides = [];
        }

        $guideSerializer = static function ($guide) {
            if (is_array($guide)) {
                $title = trim((string) ($guide['title'] ?? $guide['label'] ?? ''));
                $prompt = trim((string) ($guide['prompt'] ?? $guide['description'] ?? ''));
                return htmlspecialchars($prompt === '' ? $title : $title . '|' . $prompt);
            }

            return htmlspecialchars(trim((string) $guide));
        };

        $moduleMicroGuides = [];
        foreach (($moduleGuides['micro'] ?? []) as $guide) {
            $serialized = $guideSerializer($guide);
            if ($serialized !== '') {
                $moduleMicroGuides[] = $serialized;
            }
        }

        $moduleMacroGuides = [];
        foreach (($moduleGuides['macro'] ?? []) as $guide) {
            $serialized = $guideSerializer($guide);
            if ($serialized !== '') {
                $moduleMacroGuides[] = $serialized;
            }
        }

        $cssTokensText = htmlspecialchars(implode("\n", array_map('strval', $moduleCssTokens)));

        $moduleRelationships = $module['relationships'] ?? [];
        if (!is_array($moduleRelationships)) {
            $moduleRelationships = [];
        }
        $moduleRelationshipLines = [];
        foreach ($moduleRelationships as $relationship) {
            if (!is_array($relationship)) {
                continue;
            }
            $type = trim((string) ($relationship['type'] ?? 'related'));
            if ($type === '') {
                $type = 'related';
            }
            $target = trim((string) ($relationship['module_key'] ?? $relationship['module_reference'] ?? ''));
            if ($target === '') {
                continue;
            }
            $label = trim((string) ($relationship['module_label'] ?? ''));
            $description = trim((string) ($relationship['description'] ?? ''));
            $parts = [$type, $target];
            if ($label !== '' && strcasecmp($label, $target) !== 0) {
                $parts[] = $label;
                if ($description !== '') {
                    $parts[] = $description;
                }
            } elseif ($description !== '') {
                $parts[] = $description;
            }
            $moduleRelationshipLines[] = htmlspecialchars(implode('|', $parts));
        }

        $statusLabel = ucfirst($moduleStatus);
        $visibilityLabel = $moduleVisibility === 'everyone' ? 'Everyone' : ucfirst($moduleVisibility);
        $body .= '<article class="content-module-card">';
        $body .= '<header><h3>' . htmlspecialchars($moduleLabel) . '</h3><p class="module-key"><code>' . htmlspecialchars($moduleKey) . '</code> · Status: <span class="module-status status-' . htmlspecialchars($moduleStatus) . '">' . htmlspecialchars($statusLabel) . '</span> · Visibility: ' . htmlspecialchars($visibilityLabel) . '</p></header>';
        if (!empty($taskProgress['summary'])) {
            $stateClass = $taskProgress['state'] ?? 'unknown';
            $statusLabel = trim((string) ($taskProgress['status_label'] ?? ''));
            $summaryText = $statusLabel !== '' ? $statusLabel . ' — ' . $taskProgress['summary'] : $taskProgress['summary'];
            $body .= '<p class="module-task-summary task-state-' . htmlspecialchars($stateClass) . '">' . htmlspecialchars($summaryText) . '</p>';
        }
        $body .= '<form method="post" action="/setup.php" class="content-module-form">';
        $body .= '<input type="hidden" name="action" value="update_content_module">';
        $body .= '<input type="hidden" name="module_id" value="' . htmlspecialchars((string) $moduleId) . '">';
        $body .= '<div class="field-grid roadmap-basic-grid">';
        $body .= '<label class="field"><span class="field-label">Label</span><span class="field-control"><input type="text" name="label" value="' . htmlspecialchars($moduleLabel) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Dataset</span><span class="field-control"><select name="dataset">';
        $seenDataset = false;
        foreach ($datasetOptions as $datasetKey => $datasetLabel) {
            $selected = $datasetKey === $moduleDataset ? ' selected' : '';
            if ($selected !== '') {
                $seenDataset = true;
            }
            $body .= '<option value="' . htmlspecialchars($datasetKey) . '"' . $selected . '>' . htmlspecialchars((string) $datasetLabel) . '</option>';
        }
        if (!$seenDataset && $moduleDataset !== '') {
            $body .= '<option value="' . htmlspecialchars($moduleDataset) . '" selected>' . htmlspecialchars(ucfirst($moduleDataset)) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Format hint</span><span class="field-control"><input type="text" name="format" value="' . htmlspecialchars($moduleFormat) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
        foreach (['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'] as $value => $labelOption) {
            $selected = $moduleStatus === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($labelOption) . '</option>';
        }
        $body .= '</select></span><span class="field-description">Draft modules stay hidden from members until activated.</span></label>';
        $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
        foreach (['everyone' => 'Everyone', 'members' => 'Members', 'admins' => 'Admins'] as $value => $labelOption) {
            $selected = $moduleVisibility === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($labelOption) . '</option>';
        }
        $body .= '</select></span><span class="field-description">Control who can launch this module from the feed.</span></label>';
        $body .= '</div>';
        $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="3">' . htmlspecialchars($moduleDescription) . '</textarea></span></label>';
        if (!empty($roles)) {
            $body .= '<fieldset class="field"><span class="field-label">Allowed roles</span><span class="field-control">';
            foreach ($roles as $roleKey => $roleDescription) {
                $roleValue = strtolower((string) $roleKey);
                $checked = in_array($roleValue, $moduleAllowedRoles, true) ? ' checked' : '';
                $body .= '<label><input type="checkbox" name="allowed_roles[]" value="' . htmlspecialchars($roleValue) . '"' . $checked . '> ' . htmlspecialchars(ucfirst((string) $roleKey)) . '</label>';
            }
            $body .= '</span><span class="field-description">Leave empty to allow every role within the selected visibility.</span></fieldset>';
        } else {
            $body .= '<label class="field"><span class="field-label">Allowed roles</span><span class="field-control"><input type="text" name="allowed_roles" value="' . htmlspecialchars(implode(', ', $moduleAllowedRoles)) . '" placeholder="admin, moderator"></span><span class="field-description">Comma-separated role slugs to limit access.</span></label>';
        }
        $body .= '<div class="field-grid content-module-grid">';
        $body .= '<label class="field"><span class="field-label">Categories</span><span class="field-control"><textarea name="categories" rows="4">' . $categoryText . '</textarea></span><span class="field-description">One category per line.</span></label>';
        $body .= '<label class="field"><span class="field-label">Fields</span><span class="field-control"><textarea name="fields" rows="6">' . implode("\n", $fieldLines) . '</textarea></span><span class="field-description">Use <code>Label|Description</code> per line to describe sub-prompts.</span></label>';
        $body .= '<label class="field"><span class="field-label">Checklist</span><span class="field-control"><textarea name="tasks" rows="5">' . implode("\n", $taskLines) . '</textarea></span><span class="field-description">Capture repeatable steps (<code>Task|Description|complete|Owner|Due date|Priority|Notes</code> per line &mdash; leave trailing fields blank when not needed).</span></label>';
        $body .= '<label class="field"><span class="field-label">Profile prompts</span><span class="field-control"><textarea name="profile_prompts" rows="6">' . implode("\n", $profileLines) . '</textarea></span><span class="field-description">Help members extend their profiles when this module is used.</span></label>';
        $body .= '<label class="field"><span class="field-label">Wizard steps</span><span class="field-control"><textarea name="wizard_steps" rows="5">' . implode("\n", $wizardLines) . '</textarea></span><span class="field-description">Step-by-step guidance (<code>Title|Prompt</code> per line).</span></label>';
        $body .= '<label class="field"><span class="field-label">Micro guides</span><span class="field-control"><textarea name="micro_guides" rows="4">' . implode("\n", $moduleMicroGuides) . '</textarea></span><span class="field-description">Short prompts for individual publishing steps (<code>Title|Prompt</code> per line).</span></label>';
        $body .= '<label class="field"><span class="field-label">Macro guides</span><span class="field-control"><textarea name="macro_guides" rows="4">' . implode("\n", $moduleMacroGuides) . '</textarea></span><span class="field-description">High-level rollout instructions for teams (<code>Title|Prompt</code> per line).</span></label>';
        $body .= '<label class="field"><span class="field-label">Relationships</span><span class="field-control"><textarea name="relationships" rows="4">' . implode("\n", $moduleRelationshipLines) . '</textarea></span><span class="field-description">Map connected modules (<code>Type|Module key|Optional label|Optional description</code> per line).</span></label>';
        $body .= '<label class="field"><span class="field-label">CSS tokens</span><span class="field-control"><textarea name="css_tokens" rows="4">' . $cssTokensText . '</textarea></span><span class="field-description">Reference tokens pulled from the CSS value library.</span></label>';
        $body .= '</div>';
        $body .= '<div class="action-row">';
        $body .= '<button type="submit" class="button primary">Save module</button>';
        $body .= '</div>';
        $body .= '</form>';
        $body .= '<form method="post" action="/setup.php" class="content-module-delete" onsubmit="return confirm(\'Delete this content module?\');">';
        $body .= '<input type="hidden" name="action" value="delete_content_module">';
        $body .= '<input type="hidden" name="module_id" value="' . htmlspecialchars((string) $moduleId) . '">';
        $body .= '<button type="submit" class="button danger">Delete module</button>';
        $body .= '</form>';
        $body .= '</article>';
    }

    $body .= '<article class="content-module-card create">';
    $body .= '<header><h3>Create new module</h3><p>Compose a new modular content type using the blueprint resources.</p></header>';
    $body .= '<form method="post" action="/setup.php" class="content-module-form">';
    $body .= '<input type="hidden" name="action" value="create_content_module">';
    $body .= '<div class="field-grid roadmap-basic-grid">';
    $body .= '<label class="field"><span class="field-label">Label</span><span class="field-control"><input type="text" name="label" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Dataset</span><span class="field-control"><select name="dataset">';
    foreach ($datasetOptions as $datasetKey => $datasetLabel) {
        $body .= '<option value="' . htmlspecialchars($datasetKey) . '">' . htmlspecialchars((string) $datasetLabel) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Format hint</span><span class="field-control"><input type="text" name="format" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
    foreach (['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'] as $value => $labelOption) {
        $selected = $value === 'active' ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($labelOption) . '</option>';
    }
    $body .= '</select></span><span class="field-description">Draft modules stay hidden from members until you publish them.</span></label>';
    $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
    foreach (['everyone' => 'Everyone', 'members' => 'Members', 'admins' => 'Admins'] as $value => $labelOption) {
        $selected = $value === 'members' ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($labelOption) . '</option>';
    }
    $body .= '</select></span><span class="field-description">Restrict who can launch the module from the feed.</span></label>';
    $body .= '</div>';
    $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="3"></textarea></span></label>';
    if (!empty($roles)) {
        $body .= '<fieldset class="field"><span class="field-label">Allowed roles</span><span class="field-control">';
        foreach ($roles as $roleKey => $roleDescription) {
            $roleValue = strtolower((string) $roleKey);
            $body .= '<label><input type="checkbox" name="allowed_roles[]" value="' . htmlspecialchars($roleValue) . '"> ' . htmlspecialchars(ucfirst((string) $roleKey)) . '</label>';
        }
        $body .= '</span><span class="field-description">Leave empty to allow every role within the selected visibility.</span></fieldset>';
    } else {
        $body .= '<label class="field"><span class="field-label">Allowed roles</span><span class="field-control"><input type="text" name="allowed_roles" placeholder="admin, moderator"></span><span class="field-description">Comma-separated role slugs to limit access.</span></label>';
    }
    $defaultCategoryLines = array_slice(array_map(static function ($category) {
        return (string) ($category['title'] ?? $category);
    }, $contentBlueprints['categories'] ?? []), 0, 6);
    $defaultProfileLines = array_slice(array_map(static function ($prompt) {
        if (!is_array($prompt)) {
            return (string) $prompt;
        }
        $label = trim((string) ($prompt['name'] ?? $prompt['label'] ?? ''));
        $description = trim((string) ($prompt['description'] ?? ''));
        return $description === '' ? $label : $label . '|' . $description;
    }, $contentBlueprints['profile_fields'] ?? []), 0, 4);
    $contextLabels = $contentBlueprints['contexts']['labels'] ?? ['Outline', 'Structure', 'Publish'];
    $contextDescriptions = $contentBlueprints['contexts']['descriptions'] ?? [];
    $defaultWizardLines = [];
    foreach (array_slice($contextLabels, 0, 3, true) as $index => $title) {
        $label = (string) $title;
        $prompt = trim((string) ($contextDescriptions[$index] ?? ''));
        $defaultWizardLines[] = $prompt === '' ? $label : $label . '|' . $prompt;
    }
    if (empty($defaultWizardLines)) {
        $defaultWizardLines = ['Outline', 'Structure', 'Publish'];
    }
    $defaultMicroLines = [];
    foreach (array_slice($contextLabels, 0, 4, true) as $index => $title) {
        $label = (string) $title;
        $prompt = trim((string) ($contextDescriptions[$index] ?? ''));
        $defaultMicroLines[] = $prompt === '' ? $label : $label . '|' . $prompt;
    }
    if (empty($defaultMicroLines)) {
        $defaultMicroLines = ['Outline', 'Structure', 'Publish', 'Review'];
    }
    $defaultMacroLines = [];
    $macroCategories = array_slice($defaultCategoryLines, 0, 3);
    if (!empty($macroCategories)) {
        $defaultMacroLines[] = 'Align categories|' . implode(', ', $macroCategories) . ' make it easy to group related entries.';
    }
    $defaultMacroLines[] = 'Coordinate roles|Document who curates the module and which roles approve entries.';
    $defaultMacroLines[] = 'Track outcomes|Note how teams will review module performance after publishing.';

    $defaultTaskLines = [
        'Draft outline|Capture the intended structure before writing.',
        'Link supporting modules|Reference related workflows members should review.',
        'Schedule follow-up|Note when to revisit or expand the entry.'
    ];

    $body .= '<div class="field-grid content-module-grid">';
    $body .= '<label class="field"><span class="field-label">Categories</span><span class="field-control"><textarea name="categories" rows="4">' . htmlspecialchars(implode("\n", $defaultCategoryLines)) . '</textarea></span><span class="field-description">Seed with blueprint categories (one per line).</span></label>';
    $body .= '<label class="field"><span class="field-label">Fields</span><span class="field-control"><textarea name="fields" rows="6"></textarea></span><span class="field-description">List <code>Label|Description</code> prompts to collect for each entry.</span></label>';
    $body .= '<label class="field"><span class="field-label">Checklist</span><span class="field-control"><textarea name="tasks" rows="5">' . htmlspecialchars(implode("\n", $defaultTaskLines)) . '</textarea></span><span class="field-description">Outline repeatable steps (<code>Task|Optional description|complete</code> per line).</span></label>';
    $body .= '<label class="field"><span class="field-label">Profile prompts</span><span class="field-control"><textarea name="profile_prompts" rows="6">' . htmlspecialchars(implode("\n", $defaultProfileLines)) . '</textarea></span><span class="field-description">Help members extend their profiles when this module is used.</span></label>';
    $body .= '<label class="field"><span class="field-label">Relationships</span><span class="field-control"><textarea name="relationships" rows="4" placeholder="related|create-article|Create Article|Summarise agreements in article form"></textarea></span><span class="field-description">Connect complementary modules (<code>Type|Module key|Optional label|Optional description</code> per line).</span></label>';
    $body .= '<label class="field"><span class="field-label">Wizard steps</span><span class="field-control"><textarea name="wizard_steps" rows="5">' . htmlspecialchars(implode("\n", $defaultWizardLines)) . '</textarea></span><span class="field-description">Step-by-step guidance (<code>Title|Prompt</code> per line).</span></label>';
    $body .= '<label class="field"><span class="field-label">Micro guides</span><span class="field-control"><textarea name="micro_guides" rows="4">' . htmlspecialchars(implode("\n", $defaultMicroLines)) . '</textarea></span><span class="field-description">Short prompts for individual publishing steps (<code>Title|Prompt</code> per line).</span></label>';
    $body .= '<label class="field"><span class="field-label">Macro guides</span><span class="field-control"><textarea name="macro_guides" rows="4">' . htmlspecialchars(implode("\n", $defaultMacroLines)) . '</textarea></span><span class="field-description">High-level rollout instructions for teams (<code>Title|Prompt</code> per line).</span></label>';
    $body .= '<label class="field"><span class="field-label">CSS tokens</span><span class="field-control"><textarea name="css_tokens" rows="4">' . htmlspecialchars(implode("\n", array_slice($contentBlueprints['css_values'] ?? [], 0, 8))) . '</textarea></span></label>';
    $body .= '</div>';
    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Create module</button>';
    $body .= '</div>';
    $body .= '</form>';
    $body .= '</article>';

    $moduleBlueprints = $contentBlueprints['module_blueprints'] ?? [];
    if (!empty($moduleBlueprints)) {
        $body .= '<aside class="content-blueprint-library">';
        $body .= '<h3>Blueprint library</h3>';
        $body .= '<p>Select a ready-made blueprint to seed a new module. Each blueprint bundles field prompts, suggested categories, and wizard copy.</p>';
        foreach ($moduleBlueprints as $index => $blueprint) {
            $label = trim((string) ($blueprint['title'] ?? 'Blueprint')); 
            $format = trim((string) ($blueprint['format'] ?? ''));
            $description = trim((string) ($blueprint['description'] ?? ''));
            $fields = [];
            foreach ($blueprint['fields'] ?? [] as $field) {
                $fields[] = [
                    'label' => trim((string) ($field['title'] ?? '')),
                    'description' => trim((string) ($field['description'] ?? '')),
                ];
            }

            $blueprintCategories = array_slice(array_map(static function ($category) {
                return (string) ($category['title'] ?? $category);
            }, $contentBlueprints['categories'] ?? []), 0, 6);

            $profilePrompts = array_slice(array_map(static function ($prompt) {
                if (!is_array($prompt)) {
                    return ['label' => (string) $prompt, 'description' => ''];
                }
                return [
                    'label' => trim((string) ($prompt['name'] ?? $prompt['label'] ?? '')),
                    'description' => trim((string) ($prompt['description'] ?? '')),
                ];
            }, $contentBlueprints['profile_fields'] ?? []), 0, 4);

            $wizardSteps = [];
            $contextLabels = $contentBlueprints['contexts']['labels'] ?? [];
            $contextDescriptions = $contentBlueprints['contexts']['descriptions'] ?? [];
            foreach ($contextLabels as $stepIndex => $stepLabel) {
                $wizardSteps[] = [
                    'title' => (string) $stepLabel,
                    'prompt' => trim((string) ($contextDescriptions[$stepIndex] ?? '')),
                ];
            }
            if (empty($wizardSteps)) {
                $wizardSteps = [
                    ['title' => 'Outline', 'prompt' => $description],
                    ['title' => 'Structure', 'prompt' => 'Collect supporting information for this module.'],
                    ['title' => 'Publish', 'prompt' => 'Confirm privacy, notifications, and delivery options.'],
                ];
            }

            $microGuides = [];
            foreach ($contextLabels as $stepIndex => $stepLabel) {
                $microGuides[] = [
                    'title' => (string) $stepLabel,
                    'prompt' => trim((string) ($contextDescriptions[$stepIndex] ?? '')),
                ];
            }
            if (empty($microGuides)) {
                $microGuides = [
                    ['title' => 'Outline', 'prompt' => $description],
                    ['title' => 'Structure', 'prompt' => 'Clarify the data members should gather before publishing.'],
                    ['title' => 'Publish', 'prompt' => 'Note where this module appears and who maintains it.'],
                ];
            }

            $macroGuides = [];
            $categorySummary = array_slice($blueprintCategories, 0, 3);
            if (!empty($categorySummary)) {
                $macroGuides[] = [
                    'title' => 'Align categories',
                    'prompt' => 'Group entries under ' . implode(', ', $categorySummary) . ' to reinforce navigation.',
                ];
            }
            $macroGuides[] = [
                'title' => 'Coordinate roles',
                'prompt' => 'List the roles that approve drafts and steward published entries.',
            ];
            $macroGuides[] = [
                'title' => 'Track outcomes',
                'prompt' => 'Decide which datasets capture follow-up actions once modules are published.',
            ];

            $payload = [
                'label' => $label,
                'format' => $format,
                'description' => $description,
                'categories' => $blueprintCategories,
                'fields' => $fields,
                'profile_prompts' => $profilePrompts,
                'wizard_steps' => $wizardSteps,
                'css_tokens' => array_slice($contentBlueprints['css_values'] ?? [], 0, 8),
                'guides' => [
                    'micro' => $microGuides,
                    'macro' => $macroGuides,
                ],
            ];
            $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (!is_string($encodedPayload)) {
                $encodedPayload = '{}';
            }

            $body .= '<article class="blueprint-card">';
            $body .= '<header><h4>' . htmlspecialchars($label) . '</h4>';
            if ($format !== '') {
                $body .= '<p class="blueprint-format">Format: ' . htmlspecialchars($format) . '</p>';
            }
            $body .= '</header>';
            if ($description !== '') {
                $body .= '<p class="blueprint-description">' . htmlspecialchars($description) . '</p>';
            }
            if (!empty($fields)) {
                $body .= '<ul class="blueprint-fields">';
                foreach ($fields as $field) {
                    $body .= '<li><strong>' . htmlspecialchars($field['label']) . '</strong>';
                    if ($field['description'] !== '') {
                        $body .= '<span> — ' . htmlspecialchars($field['description']) . '</span>';
                    }
                    $body .= '</li>';
                }
                $body .= '</ul>';
            }
            if (!empty($microGuides) || !empty($macroGuides)) {
                $body .= '<details class="blueprint-guides"><summary>Guidance</summary>';
                if (!empty($microGuides)) {
                    $body .= '<h5>Micro</h5><ul>';
                    foreach ($microGuides as $guide) {
                        $body .= '<li><strong>' . htmlspecialchars($guide['title']) . '</strong>';
                        if ($guide['prompt'] !== '') {
                            $body .= '<span> — ' . htmlspecialchars($guide['prompt']) . '</span>';
                        }
                        $body .= '</li>';
                    }
                    $body .= '</ul>';
                }
                if (!empty($macroGuides)) {
                    $body .= '<h5>Macro</h5><ul>';
                    foreach ($macroGuides as $guide) {
                        $body .= '<li><strong>' . htmlspecialchars($guide['title']) . '</strong>';
                        if ($guide['prompt'] !== '') {
                            $body .= '<span> — ' . htmlspecialchars($guide['prompt']) . '</span>';
                        }
                        $body .= '</li>';
                    }
                    $body .= '</ul>';
                }
                $body .= '</details>';
            }
            $body .= '<form method="post" action="/setup.php" class="blueprint-import">';
            $body .= '<input type="hidden" name="action" value="adopt_content_blueprint">';
            $body .= '<input type="hidden" name="blueprint" value="' . htmlspecialchars($encodedPayload, ENT_QUOTES) . '">';
            $body .= '<button type="submit" class="button">Import module</button>';
            $body .= '</form>';
            $body .= '</article>';
        }

        $body .= '<details class="blueprint-reference">';
        $body .= '<summary>Reference libraries</summary>';
        $body .= '<div class="reference-columns">';
        $body .= '<section><h4>Categories</h4><ul>';
        foreach (array_slice($contentBlueprints['categories'] ?? [], 0, 12) as $category) {
            if (is_array($category)) {
                $body .= '<li><strong>' . htmlspecialchars((string) ($category['title'] ?? '')) . '</strong><span> — ' . htmlspecialchars((string) ($category['description'] ?? '')) . '</span></li>';
            } else {
                $body .= '<li>' . htmlspecialchars((string) $category) . '</li>';
            }
        }
        $body .= '</ul></section>';
        $body .= '<section><h4>CSS tokens</h4><ul>';
        foreach (array_slice($contentBlueprints['css_values'] ?? [], 0, 20) as $token) {
            $body .= '<li><code>' . htmlspecialchars((string) $token) . '</code></li>';
        }
        $body .= '</ul></section>';
        $body .= '<section><h4>HTML elements</h4><ul>';
        foreach (array_slice($contentBlueprints['html_elements'] ?? [], 0, 20) as $element) {
            $body .= '<li><code>' . htmlspecialchars((string) $element) . '</code></li>';
        }
        $body .= '</ul></section>';
        $body .= '</div>';
        $body .= '<p class="reference-note">Full libraries live under <code>/assets/xml</code> if you need deeper exploration.</p>';
        $body .= '</details>';
        $body .= '</aside>';
    }

    $body .= '</section>';

    $body .= '<section class="pages-manager">';
    $body .= '<h2>Page Management</h2>';
    $body .= '<p>Create and curate navigation-ready pages without editing code. Assign visibility, templates, and navigation stati';
    $body .= 'us per page.</p>';

    if (empty($pageRecords)) {
        $body .= '<p class="notice info">No pages published yet. Use the form below to create the first one.</p>';
    }

    foreach ($pageRecords as $page) {
        $pageId = (int) ($page['id'] ?? 0);
        $pageTitle = $page['title'] ?? 'Page';
        $pageSlug = $page['slug'] ?? '';
        $pageSummary = $page['summary'] ?? '';
        $pageContent = $page['content'] ?? '';
        $pageVisibility = $page['visibility'] ?? 'public';
        $pageFormat = $page['format'] ?? 'html';
        $pageTemplate = $page['template'] ?? 'standard';
        $pageRoles = array_map('strval', $page['allowed_roles'] ?? []);
        $pageNav = !empty($page['show_in_navigation']);

        $body .= '<article class="page-card" data-page="' . htmlspecialchars((string) $pageSlug) . '">';
        $body .= '<header><h3>' . htmlspecialchars($pageTitle) . '</h3>';
        $body .= '<p class="page-card-meta"><code>' . htmlspecialchars((string) $pageSlug) . '</code> · Visibility: ' . htmlspecialchars(ucfirst((string) $pageVisibility)) . '</p>';
        $body .= '</header>';
        $body .= '<form method="post" action="/setup.php" class="page-form">';
        $body .= '<input type="hidden" name="action" value="update_page">';
        $body .= '<input type="hidden" name="page_id" value="' . htmlspecialchars((string) $pageId) . '">';
        $body .= '<div class="field-grid roadmap-assignment-grid">';
        $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value="' . htmlspecialchars($pageTitle) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Slug</span><span class="field-control"><input type="text" name="slug" value="' . htmlspecialchars((string) $pageSlug) . '"></span><span class="field-description">Used for the page URL.</span></label>';
        $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><input type="text" name="summary" value="' . htmlspecialchars($pageSummary) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Format</span><span class="field-control"><select name="format">';
        foreach (['html' => 'HTML', 'text' => 'Plain text'] as $value => $labelOption) {
            $selected = $pageFormat === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($labelOption) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Template</span><span class="field-control"><input type="text" name="template" value="' . htmlspecialchars($pageTemplate) . '"></span><span class="field-description">Reference a template keyword for layout variations.</span></label>';
        $body .= '</div>';

        $body .= '<label class="field"><span class="field-label">Content</span><span class="field-control"><textarea name="content" rows="8">' . htmlspecialchars($pageContent) . '</textarea></span></label>';

        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
        foreach (['public' => 'Public', 'members' => 'Members', 'roles' => 'Selected roles'] as $value => $labelOption) {
            $selected = $pageVisibility === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($labelOption) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<fieldset class="field"><legend>Roles permitted</legend><div class="field-checkbox-group">';
        foreach ($roles as $roleKey => $roleDescription) {
            $checked = in_array((string) $roleKey, $pageRoles, true) ? ' checked' : '';
            $body .= '<label><input type="checkbox" name="allowed_roles[]" value="' . htmlspecialchars((string) $roleKey) . '"' . $checked . '> ' . htmlspecialchars(ucfirst((string) $roleKey)) . '</label>';
        }
        $body .= '</div><p class="field-description">Only used when visibility is set to selected roles.</p></fieldset>';
        $checkedNav = $pageNav ? ' checked' : '';
        $body .= '<label class="field-toggle"><input type="checkbox" name="show_in_navigation" value="1"' . $checkedNav . '> Show in navigation</label>';
        $body .= '</div>';

        $body .= '<div class="action-row">';
        $body .= '<button type="submit" class="button primary">Save page</button>';
        $body .= '</div>';
        $body .= '</form>';

        $body .= '<form method="post" action="/setup.php" class="page-delete-form" onsubmit="return confirm(\'Delete this page?\');">';
        $body .= '<input type="hidden" name="action" value="delete_page">';
        $body .= '<input type="hidden" name="page_id" value="' . htmlspecialchars((string) $pageId) . '">';
        $body .= '<button type="submit" class="button danger">Delete page</button>';
        $body .= '</form>';

        $body .= '</article>';
    }

    $body .= '<article class="page-card create">';
    $body .= '<header><h3>Create new page</h3><p>Draft a new page with full control over visibility and placement.</p></header>';
    $body .= '<form method="post" action="/setup.php" class="page-form">';
    $body .= '<input type="hidden" name="action" value="create_page">';
    $body .= '<div class="field-grid roadmap-basic-grid">';
    $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Slug</span><span class="field-control"><input type="text" name="slug" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><input type="text" name="summary" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Format</span><span class="field-control"><select name="format">';
    foreach (['html' => 'HTML', 'text' => 'Plain text'] as $value => $labelOption) {
        $body .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($labelOption) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Template</span><span class="field-control"><input type="text" name="template" value="standard"></span></label>';
    $body .= '</div>';
    $body .= '<label class="field"><span class="field-label">Content</span><span class="field-control"><textarea name="content" rows="6"></textarea></span></label>';
    $body .= '<div class="field-grid roadmap-assignment-grid">';
    $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
    foreach (['public' => 'Public', 'members' => 'Members', 'roles' => 'Selected roles'] as $value => $labelOption) {
        $body .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($labelOption) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<fieldset class="field"><legend>Roles permitted</legend><div class="field-checkbox-group">';
    foreach ($roles as $roleKey => $roleDescription) {
        $body .= '<label><input type="checkbox" name="allowed_roles[]" value="' . htmlspecialchars((string) $roleKey) . '"> ' . htmlspecialchars(ucfirst((string) $roleKey)) . '</label>';
    }
    $body .= '</div><p class="field-description">Only active when restricting to selected roles.</p></fieldset>';
    $body .= '<label class="field-toggle"><input type="checkbox" name="show_in_navigation" value="1" checked> Show in navigation</label>';
    $body .= '</div>';
    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Create page</button>';
    $body .= '</div>';
    $body .= '</form>';
    $body .= '</article>';

    $body .= '</section>';

    $statusLabels = [
        'built' => 'Built',
        'in_progress' => 'In progress',
        'planned' => 'Planned',
        'on_hold' => 'On hold',
    ];
    $statusDescriptions = [
        'built' => 'Completed and available to everyone.',
        'in_progress' => 'Currently being delivered by the team.',
        'planned' => 'Prioritised for an upcoming milestone.',
        'on_hold' => 'Paused until prerequisites are met.',
    ];
    $statusCounts = [];
    $statusRank = [];
    $indexCounter = 0;
    foreach ($statusLabels as $statusKey => $label) {
        $statusCounts[$statusKey] = 0;
        $statusRank[$statusKey] = $indexCounter++;
    }

    $statusList = $projectStatusRecords;
    $progressTotal = 0;
    $progressCount = 0;
    foreach ($statusList as $record) {
        $state = (string) ($record['status'] ?? 'planned');
        if (!isset($statusCounts[$state])) {
            $statusCounts[$state] = 0;
            $statusRank[$state] = $indexCounter++;
            $statusLabels[$state] = ucwords(str_replace('_', ' ', $state));
            $statusDescriptions[$state] = 'Custom status provided by administrators.';
        }
        $statusCounts[$state]++;

        $progress = (int) ($record['progress'] ?? 0);
        if ($progress < 0) {
            $progress = 0;
        }
        if ($progress > 100) {
            $progress = 100;
        }
        $progressTotal += $progress;
        $progressCount++;
    }

    if (!empty($statusList)) {
        usort($statusList, static function (array $a, array $b) use ($statusRank) {
            $stateA = (string) ($a['status'] ?? 'planned');
            $stateB = (string) ($b['status'] ?? 'planned');
            $rankA = $statusRank[$stateA] ?? PHP_INT_MAX;
            $rankB = $statusRank[$stateB] ?? PHP_INT_MAX;
            if ($rankA === $rankB) {
                return ((int) ($b['progress'] ?? 0)) <=> ((int) ($a['progress'] ?? 0));
            }

            return $rankA <=> $rankB;
        });
    }

    $averageProgress = $progressCount > 0 ? (int) round($progressTotal / $progressCount) : 0;

    $body .= '<section class="roadmap-manager">';
    $body .= '<h2>Roadmap tracker</h2>';
    $body .= '<p>Track what has shipped, what is in motion, and what is still planned so every profile, page, and dataset stays aligned.</p>';

    if (empty($statusList)) {
        $body .= '<p class="notice muted">No roadmap entries recorded yet. Use the form below to outline your first milestone.</p>';
    } else {
        $body .= '<div class="roadmap-summary">';
        foreach ($statusLabels as $key => $label) {
            $count = (int) ($statusCounts[$key] ?? 0);
            $body .= '<article class="roadmap-chip roadmap-status-' . htmlspecialchars($key) . '">';
            $body .= '<h3>' . htmlspecialchars($label) . '</h3>';
            $body .= '<p class="roadmap-total">' . $count . ' ' . ($count === 1 ? 'item' : 'items') . '</p>';
            $body .= '<p class="roadmap-description">' . htmlspecialchars($statusDescriptions[$key] ?? '') . '</p>';
            $body .= '</article>';
        }
        $body .= '<article class="roadmap-chip roadmap-progress">';
        $body .= '<h3>Average progress</h3>';
        $body .= '<p class="roadmap-total">' . $averageProgress . '%</p>';
        $body .= '<p class="roadmap-description">Mean completion across tracked work.</p>';
        $body .= '</article>';
        $body .= '</div>';
    }

    foreach ($statusList as $record) {
        $statusKey = (string) ($record['status'] ?? 'planned');
        $statusLabel = $statusLabels[$statusKey] ?? ucwords(str_replace('_', ' ', $statusKey));
        $title = trim((string) ($record['title'] ?? 'Untitled milestone'));
        $summaryText = trim((string) ($record['summary'] ?? ''));
        $category = trim((string) ($record['category'] ?? ''));
        $milestone = trim((string) ($record['milestone'] ?? ''));
        $progress = (int) ($record['progress'] ?? 0);
        if ($progress < 0) {
            $progress = 0;
        }
        if ($progress > 100) {
            $progress = 100;
        }
        $ownerRole = (string) ($record['owner_role'] ?? '');
        $ownerUserId = $record['owner_user_id'] ?? null;
        $linksValue = '';
        if (!empty($record['links']) && is_array($record['links'])) {
            $linksValue = implode("\n", array_map(static function ($link) {
                return (string) $link;
            }, $record['links']));
        }

        $metaParts = [];
        $metaParts[] = 'Status: ' . $statusLabel;
        $metaParts[] = 'Progress: ' . $progress . '%';
        if ($category !== '') {
            $metaParts[] = 'Category: ' . $category;
        }
        if ($milestone !== '') {
            $metaParts[] = 'Milestone: ' . $milestone;
        }
        if ($ownerRole !== '') {
            $metaParts[] = 'Role lead: ' . ucfirst($ownerRole);
        }
        if ($ownerUserId !== null && isset($userIndex[(string) $ownerUserId])) {
            $metaParts[] = 'Owner: @' . ($userIndex[(string) $ownerUserId]['username'] ?? $ownerUserId);
        }

        $body .= '<article class="roadmap-card">';
        $body .= '<header>';
        $body .= '<h3>' . htmlspecialchars($title) . '</h3>';
        if (!empty($metaParts)) {
            $body .= '<p class="roadmap-meta">' . htmlspecialchars(implode(' · ', $metaParts)) . '</p>';
        }
        if ($summaryText !== '') {
            $body .= '<p class="roadmap-summary-text">' . htmlspecialchars($summaryText) . '</p>';
        }
        if (!empty($record['updated_at'])) {
            $timestamp = strtotime((string) $record['updated_at']);
            if ($timestamp) {
                $body .= '<p class="roadmap-updated">Last updated ' . htmlspecialchars(date('M j, Y H:i', $timestamp)) . '</p>';
            }
        }
        $body .= '</header>';

        $body .= '<form method="post" action="/setup.php" class="roadmap-form">';
        $body .= '<input type="hidden" name="action" value="update_project_status">';
        $body .= '<input type="hidden" name="project_status_id" value="' . (int) ($record['id'] ?? 0) . '">';
        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value="' . htmlspecialchars($title) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Category</span><span class="field-control"><input type="text" name="category" value="' . htmlspecialchars($category) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Milestone</span><span class="field-control"><input type="text" name="milestone" value="' . htmlspecialchars($milestone) . '"></span></label>';
        $body .= '</div>';

        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
        foreach ($statusLabels as $value => $label) {
            $selected = $statusKey === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Progress (%)</span><span class="field-control"><input type="number" name="progress" min="0" max="100" value="' . $progress . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Owner role</span><span class="field-control"><select name="owner_role">';
        $body .= '<option value="">Unassigned</option>';
        foreach ($roles as $roleKey => $roleDescription) {
            $selected = $ownerRole === (string) $roleKey ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars((string) $roleKey) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $roleKey)) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Owner profile</span><span class="field-control"><select name="owner_user_id">';
        $body .= '<option value="">Unassigned</option>';
        foreach ($users as $user) {
            $userId = (int) ($user['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $selected = ($ownerUserId !== null && (int) $ownerUserId === $userId) ? ' selected' : '';
            $username = $user['username'] ?? ('User #' . $userId);
            $body .= '<option value="' . $userId . '"' . $selected . '>' . htmlspecialchars($username) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '</div>';

        $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><textarea name="summary" rows="3">' . htmlspecialchars($summaryText) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Reference links</span><span class="field-control"><textarea name="links" rows="3" placeholder="One link per line">' . htmlspecialchars($linksValue) . '</textarea></span><span class="field-description">Provide URLs or internal paths that contextualise this milestone.</span></label>';

        $body .= '<div class="action-row">';
        $body .= '<button type="submit" class="button primary">Save changes</button>';
        $body .= '</div>';
        $body .= '</form>';

        $body .= '<form method="post" action="/setup.php" class="roadmap-delete-form" onsubmit="return confirm(\'Remove this roadmap entry?\');">';
        $body .= '<input type="hidden" name="action" value="delete_project_status">';
        $body .= '<input type="hidden" name="project_status_id" value="' . (int) ($record['id'] ?? 0) . '">';
        $body .= '<button type="submit" class="button danger">Delete entry</button>';
        $body .= '</form>';

        $body .= '</article>';
    }

    $body .= '<article class="roadmap-card create">';
    $body .= '<header><h3>Create new roadmap entry</h3><p>Outline a feature, milestone, or enhancement and classify its status for everyone.</p></header>';
    $body .= '<form method="post" action="/setup.php" class="roadmap-form">';
    $body .= '<input type="hidden" name="action" value="create_project_status">';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Category</span><span class="field-control"><input type="text" name="category" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Milestone</span><span class="field-control"><input type="text" name="milestone" value=""></span></label>';
    $body .= '</div>';

    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
    foreach ($statusLabels as $value => $label) {
        $selected = $value === 'planned' ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Progress (%)</span><span class="field-control"><input type="number" name="progress" min="0" max="100" value="0"></span></label>';
    $body .= '<label class="field"><span class="field-label">Owner role</span><span class="field-control"><select name="owner_role">';
    $body .= '<option value="">Unassigned</option>';
    foreach ($roles as $roleKey => $roleDescription) {
        $body .= '<option value="' . htmlspecialchars((string) $roleKey) . '">' . htmlspecialchars(ucfirst((string) $roleKey)) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Owner profile</span><span class="field-control"><select name="owner_user_id">';
    $body .= '<option value="">Unassigned</option>';
    foreach ($users as $user) {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }
        $username = $user['username'] ?? ('User #' . $userId);
        $body .= '<option value="' . $userId . '">' . htmlspecialchars($username) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '</div>';

    $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><textarea name="summary" rows="3" placeholder="What is this roadmap item about?"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Reference links</span><span class="field-control"><textarea name="links" rows="3" placeholder="One link per line"></textarea></span><span class="field-description">Provide URLs, dataset names, or documentation paths that will help collaborators.</span></label>';

    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Create roadmap entry</button>';
    $body .= '</div>';
    $body .= '</form>';
    $body .= '</article>';

    $body .= '</section>';

    $knowledgeStatusLabels = [
        'published' => 'Published',
        'scheduled' => 'Scheduled',
        'draft' => 'Draft',
        'archived' => 'Archived',
    ];
    $knowledgeVisibilityOptions = ['public' => 'Public', 'members' => 'Members', 'private' => 'Private'];
    $knowledgeStatusCounts = [];
    foreach ($knowledgeStatusLabels as $key => $label) {
        $knowledgeStatusCounts[$key] = 0;
    }
    $knowledgeStatusRank = ['published' => 0, 'scheduled' => 1, 'draft' => 2, 'archived' => 3];
    $knowledgeEntries = [];
    $knowledgeTagTotals = [];
    $knowledgeCategoryIndex = [];
    foreach ($knowledgeCategoryRecords as $category) {
        if (!is_array($category)) {
            continue;
        }
        $categoryId = (int) ($category['id'] ?? 0);
        if ($categoryId <= 0) {
            continue;
        }
        $knowledgeCategoryIndex[$categoryId] = $category;
    }
    $knowledgeCategoryTotals = [];
    foreach ($knowledgeRecords as $article) {
        if (!is_array($article)) {
            continue;
        }

        $status = strtolower((string) ($article['status'] ?? $knowledgeDefaultStatus));
        if (!isset($knowledgeStatusLabels[$status])) {
            $knowledgeStatusLabels[$status] = ucwords(str_replace('_', ' ', $status));
            $knowledgeStatusCounts[$status] = 0;
            $knowledgeStatusRank[$status] = count($knowledgeStatusRank);
        }
        $knowledgeStatusCounts[$status] = ($knowledgeStatusCounts[$status] ?? 0) + 1;

        $visibility = strtolower((string) ($article['visibility'] ?? $knowledgeDefaultVisibility));
        if (!in_array($visibility, ['public', 'members', 'private'], true)) {
            $visibility = $knowledgeDefaultVisibility;
        }

        $tags = $article['tags'] ?? [];
        if (!is_array($tags)) {
            $tags = [];
        }
        $normalizedTags = [];
        foreach ($tags as $tag) {
            $normalized = strtolower(trim((string) $tag));
            if ($normalized === '') {
                continue;
            }
            $normalizedTags[] = $normalized;
            $knowledgeTagTotals[$normalized] = ($knowledgeTagTotals[$normalized] ?? 0) + 1;
        }

        $attachmentsValue = '';
        if (!empty($article['attachments']) && is_array($article['attachments'])) {
            $attachmentsValue = implode("\n", array_map('strval', $article['attachments']));
        }

        $tagsValue = '';
        if (!empty($normalizedTags)) {
            $tagsValue = implode(', ', $normalizedTags);
        }

        $categoryId = (int) ($article['category_id'] ?? 0);
        $categoryName = '';
        $categorySlug = '';
        if ($categoryId > 0 && isset($knowledgeCategoryIndex[$categoryId])) {
            $categoryRecord = $knowledgeCategoryIndex[$categoryId];
            $categoryName = (string) ($categoryRecord['name'] ?? '');
            $categorySlug = strtolower((string) ($categoryRecord['slug'] ?? ''));
            $knowledgeCategoryTotals[$categoryId] = ($knowledgeCategoryTotals[$categoryId] ?? 0) + 1;
        }

        $knowledgeEntries[] = array_merge($article, [
            'status' => $status,
            'visibility' => $visibility,
            'tags_value' => $tagsValue,
            'attachments_value' => $attachmentsValue,
            'normalized_tags' => $normalizedTags,
            'category_id' => $categoryId,
            'category_name' => $categoryName,
            'category_slug' => $categorySlug,
        ]);
    }

    if (!empty($knowledgeEntries)) {
        usort($knowledgeEntries, static function (array $a, array $b) use ($knowledgeStatusRank) {
            $rankA = $knowledgeStatusRank[$a['status'] ?? ''] ?? PHP_INT_MAX;
            $rankB = $knowledgeStatusRank[$b['status'] ?? ''] ?? PHP_INT_MAX;
            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            $timeA = strtotime((string) ($a['updated_at'] ?? $a['created_at'] ?? 'now'));
            $timeB = strtotime((string) ($b['updated_at'] ?? $b['created_at'] ?? 'now'));
            return $timeB <=> $timeA;
        });
    }

    arsort($knowledgeTagTotals);
    $showTagCloud = true;

    $body .= '<section class="knowledge-manager">';
    $body .= '<h2>Knowledge base</h2>';
    $body .= '<p>Draft, publish, and curate reference material so members can self-serve answers without leaving Filegate.</p>';

    if (!empty($knowledgeEntries)) {
        $body .= '<div class="knowledge-summary">';
        foreach ($knowledgeStatusLabels as $statusKey => $label) {
            $count = (int) ($knowledgeStatusCounts[$statusKey] ?? 0);
            $body .= '<article class="knowledge-chip knowledge-status-' . htmlspecialchars($statusKey) . '">';
            $body .= '<h3>' . htmlspecialchars($label) . '</h3>';
            $body .= '<p class="knowledge-total">' . $count . ' ' . ($count === 1 ? 'article' : 'articles') . '</p>';
            $body .= '</article>';
        }
        if ($showTagCloud && !empty($knowledgeTagTotals)) {
            $body .= '<article class="knowledge-chip knowledge-tags">';
            $body .= '<h3>Top tags</h3>';
            $tagBadges = [];
            $limit = 0;
            foreach ($knowledgeTagTotals as $tag => $total) {
                $tagBadges[] = htmlspecialchars((string) $tag) . ' <span>' . (int) $total . '</span>';
                $limit++;
                if ($limit >= 6) {
                    break;
                }
            }
            $body .= '<p class="knowledge-total">' . implode(' · ', $tagBadges) . '</p>';
            $body .= '</article>';
        }
        if (!empty($knowledgeCategoryIndex)) {
            $body .= '<article class="knowledge-chip knowledge-categories">';
            $body .= '<h3>Categories</h3>';
            $categoryBadges = [];
            foreach ($knowledgeCategoriesSorted as $category) {
                $categoryId = (int) ($category['id'] ?? 0);
                if ($categoryId <= 0) {
                    continue;
                }
                $count = (int) ($knowledgeCategoryTotals[$categoryId] ?? 0);
                $categoryBadges[] = htmlspecialchars((string) ($category['name'] ?? '')) . ' <span>' . $count . '</span>';
            }
            if (!empty($categoryBadges)) {
                $body .= '<p class="knowledge-total">' . implode(' · ', $categoryBadges) . '</p>';
            } else {
                $body .= '<p class="knowledge-total">Configured but unused. Add articles to populate categories.</p>';
            }
            $body .= '</article>';
        }
        $body .= '</div>';
    } else {
        $body .= '<p class="notice muted">No knowledge base entries yet. Use the form below to capture your first guide.</p>';
    }

    if (!empty($knowledgeCategoriesSorted)) {
        $body .= '<section class="knowledge-category-manager">';
        $body .= '<h3>Manage categories</h3>';
        $body .= '<p>Organise articles into focused collections. Visibility determines who can filter by the category.</p>';
        foreach ($knowledgeCategoriesSorted as $category) {
            if (!is_array($category)) {
                continue;
            }
            $categoryId = (int) ($category['id'] ?? 0);
            if ($categoryId <= 0) {
                continue;
            }
            $categoryName = (string) ($category['name'] ?? 'Untitled category');
            $categorySlug = (string) ($category['slug'] ?? '');
            $categoryDescription = (string) ($category['description'] ?? '');
            $categoryVisibility = strtolower((string) ($category['visibility'] ?? 'public'));
            if (!in_array($categoryVisibility, ['public', 'members', 'private'], true)) {
                $categoryVisibility = 'public';
            }
            $categoryOrdering = (int) ($category['ordering'] ?? 0);
            $count = (int) ($knowledgeCategoryTotals[$categoryId] ?? 0);

            $body .= '<article class="knowledge-category-card">';
            $body .= '<header><h4>' . htmlspecialchars($categoryName) . '</h4><p class="knowledge-category-meta">Slug: ' . htmlspecialchars($categorySlug) . ' · Articles: ' . $count . '</p></header>';
            $body .= '<form method="post" action="/setup.php" class="knowledge-category-form">';
            $body .= '<input type="hidden" name="action" value="update_knowledge_category">';
            $body .= '<input type="hidden" name="knowledge_category_id" value="' . $categoryId . '">';
            $body .= '<label class="field"><span class="field-label">Name</span><span class="field-control"><input type="text" name="name" value="' . htmlspecialchars($categoryName) . '"></span></label>';
            $body .= '<label class="field"><span class="field-label">Slug</span><span class="field-control"><input type="text" name="slug" value="' . htmlspecialchars($categorySlug) . '"></span></label>';
            $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="2">' . htmlspecialchars($categoryDescription) . '</textarea></span></label>';
            $body .= '<div class="field-grid">';
            $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
            foreach ($knowledgeVisibilityOptions as $value => $label) {
                $selected = $value === $categoryVisibility ? ' selected' : '';
                $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
            }
            $body .= '</select></span></label>';
            $body .= '<label class="field"><span class="field-label">Ordering</span><span class="field-control"><input type="number" name="ordering" value="' . $categoryOrdering . '" min="0"></span></label>';
            $body .= '</div>';
            $body .= '<div class="action-row">';
            $body .= '<button type="submit" class="button primary">Save category</button>';
            $body .= '</div>';
            $body .= '</form>';

            $body .= '<form method="post" action="/setup.php" class="knowledge-category-delete" onsubmit="return confirm(\'Delete this category? Articles will be left uncategorised.\');">';
            $body .= '<input type="hidden" name="action" value="delete_knowledge_category">';
            $body .= '<input type="hidden" name="knowledge_category_id" value="' . $categoryId . '">';
            $body .= '<button type="submit" class="button danger">Delete category</button>';
            $body .= '</form>';
            $body .= '</article>';
        }
        $body .= '</section>';
    }

    $body .= '<section class="knowledge-category-create">';
    $body .= '<h3>Create category</h3>';
    $body .= '<p>Add another collection to group similar knowledge base articles.</p>';
    $body .= '<form method="post" action="/setup.php" class="knowledge-category-form">';
    $body .= '<input type="hidden" name="action" value="create_knowledge_category">';
    $body .= '<label class="field"><span class="field-label">Name</span><span class="field-control"><input type="text" name="name" required></span></label>';
    $body .= '<label class="field"><span class="field-label">Slug</span><span class="field-control"><input type="text" name="slug" placeholder="support"></span></label>';
    $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="2" placeholder="Explain how this category should be used."></textarea></span></label>';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
    foreach ($knowledgeVisibilityOptions as $value => $label) {
        $body .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Ordering</span><span class="field-control"><input type="number" name="ordering" value="' . ($knowledgeCategoryRecords ? count($knowledgeCategoryRecords) + 1 : 1) . '" min="0"></span></label>';
    $body .= '</div>';
    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Add category</button>';
    $body .= '</div>';
    $body .= '</form>';
    $body .= '</section>';

    foreach ($knowledgeEntries as $article) {
        $articleId = (int) ($article['id'] ?? 0);
        $title = trim((string) ($article['title'] ?? 'Untitled article'));
        $slug = trim((string) ($article['slug'] ?? ''));
        $summary = trim((string) ($article['summary'] ?? ''));
        $content = (string) ($article['content'] ?? '');
        $status = (string) ($article['status'] ?? $knowledgeDefaultStatus);
        $visibility = (string) ($article['visibility'] ?? $knowledgeDefaultVisibility);
        $template = trim((string) ($article['template'] ?? 'article'));
        $tagsValue = (string) ($article['tags_value'] ?? '');
        $attachmentsValue = (string) ($article['attachments_value'] ?? '');
        $authorUserId = (int) ($article['author_user_id'] ?? 0);
        $articleCategoryId = (int) ($article['category_id'] ?? 0);
        $articleCategoryName = (string) ($article['category_name'] ?? '');
        $articleCategorySlug = (string) ($article['category_slug'] ?? '');
        $updatedAt = (string) ($article['updated_at'] ?? $article['created_at'] ?? '');
        $updatedLabel = '';
        if ($updatedAt !== '') {
            $timestamp = strtotime($updatedAt);
            if ($timestamp) {
                $updatedLabel = date('M j, Y', $timestamp);
            }
        }

        $body .= '<article class="knowledge-admin-card">';
        $body .= '<header class="knowledge-admin-header">';
        $body .= '<h3>' . htmlspecialchars($title) . '</h3>';
        $metaParts = [];
        $metaParts[] = 'Status: ' . htmlspecialchars($knowledgeStatusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)));
        $metaParts[] = 'Visibility: ' . htmlspecialchars($knowledgeVisibilityOptions[$visibility] ?? ucfirst($visibility));
        if ($articleCategoryName !== '') {
            $categoryDisplay = $articleCategorySlug !== ''
                ? '<a href="/knowledge.php?category=' . urlencode(strtolower($articleCategorySlug)) . '">' . htmlspecialchars($articleCategoryName) . '</a>'
                : htmlspecialchars($articleCategoryName);
            $metaParts[] = 'Category: ' . $categoryDisplay;
        }
        if ($updatedLabel !== '') {
            $metaParts[] = 'Updated ' . $updatedLabel;
        }
        $body .= '<p class="knowledge-admin-meta">' . implode(' · ', $metaParts) . '</p>';
        $body .= '</header>';

        $body .= '<form method="post" action="/setup.php" class="knowledge-form">';
        $body .= '<input type="hidden" name="action" value="update_knowledge_article">';
        $body .= '<input type="hidden" name="knowledge_article_id" value="' . $articleId . '">';
        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value="' . htmlspecialchars($title) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Slug</span><span class="field-control"><input type="text" name="slug" value="' . htmlspecialchars($slug) . '"></span><span class="field-description">Used for the knowledge base URL.</span></label>';
        $body .= '<label class="field"><span class="field-label">Template</span><span class="field-control"><input type="text" name="template" value="' . htmlspecialchars($template) . '"></span><span class="field-description">Match template keywords with front-end layouts.</span></label>';
        if (!empty($knowledgeCategoryIndex)) {
            $body .= '<label class="field"><span class="field-label">Category</span><span class="field-control"><select name="category_id">';
            $body .= '<option value="">Unassigned</option>';
            foreach ($knowledgeCategoriesSorted as $category) {
                $categoryId = (int) ($category['id'] ?? 0);
                if ($categoryId <= 0) {
                    continue;
                }
                $selected = $articleCategoryId === $categoryId ? ' selected' : '';
                $body .= '<option value="' . $categoryId . '"' . $selected . '>' . htmlspecialchars((string) ($category['name'] ?? '')) . '</option>';
            }
            $body .= '</select></span><span class="field-description">Organise this article within a knowledge category.</span></label>';
        }
        $body .= '</div>';

        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
        foreach ($knowledgeStatusLabels as $value => $label) {
            $selected = $value === $status ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
        foreach ($knowledgeVisibilityOptions as $value => $label) {
            $selected = $value === $visibility ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Author</span><span class="field-control"><select name="author_user_id">';
        $body .= '<option value="">No author</option>';
        foreach ($users as $user) {
            $userId = (int) ($user['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $username = $user['username'] ?? ('User #' . $userId);
            $selected = $userId === $authorUserId ? ' selected' : '';
            $body .= '<option value="' . $userId . '"' . $selected . '>' . htmlspecialchars($username) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '</div>';

        $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><textarea name="summary" rows="2">' . htmlspecialchars($summary) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Content</span><span class="field-control"><textarea name="content" rows="6">' . htmlspecialchars($content) . '</textarea></span><span class="field-description">Supports HTML, XHTML, and inline embeds.</span></label>';
        $body .= '<label class="field"><span class="field-label">Tags</span><span class="field-control"><input type="text" name="tags" value="' . htmlspecialchars($tagsValue) . '"></span><span class="field-description">Comma-separated keywords for filtering.</span></label>';
        $body .= '<label class="field"><span class="field-label">Attachments</span><span class="field-control"><textarea name="attachments" rows="3" placeholder="Local paths or upload references, one per line">' . htmlspecialchars($attachmentsValue) . '</textarea></span></label>';

        $body .= '<div class="action-row">';
        $body .= '<button type="submit" class="button primary">Save article</button>';
        $body .= '</div>';
        $body .= '</form>';

        $body .= '<form method="post" action="/setup.php" class="knowledge-delete-form" onsubmit="return confirm(\'Delete this article?\');">';
        $body .= '<input type="hidden" name="action" value="delete_knowledge_article">';
        $body .= '<input type="hidden" name="knowledge_article_id" value="' . $articleId . '">';
        $body .= '<button type="submit" class="button danger">Delete article</button>';
        $body .= '</form>';

        $body .= '</article>';
    }

    $body .= '<article class="knowledge-admin-card knowledge-create">';
    $body .= '<header><h3>Create knowledge base article</h3><p>Document guidance, best practices, or onboarding steps for members.</p></header>';
    $body .= '<form method="post" action="/setup.php" class="knowledge-form">';
    $body .= '<input type="hidden" name="action" value="create_knowledge_article">';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Slug</span><span class="field-control"><input type="text" name="slug" placeholder="getting-started"></span></label>';
    $body .= '<label class="field"><span class="field-label">Template</span><span class="field-control"><input type="text" name="template" value="article"></span></label>';
    if (!empty($knowledgeCategoryIndex)) {
        $body .= '<label class="field"><span class="field-label">Category</span><span class="field-control"><select name="category_id">';
        $body .= '<option value="">Unassigned</option>';
        foreach ($knowledgeCategoriesSorted as $category) {
            $categoryId = (int) ($category['id'] ?? 0);
            if ($categoryId <= 0) {
                continue;
            }
            $selected = $knowledgeDefaultCategory !== null && $knowledgeDefaultCategory === $categoryId ? ' selected' : '';
            $body .= '<option value="' . $categoryId . '"' . $selected . '>' . htmlspecialchars((string) ($category['name'] ?? '')) . '</option>';
        }
        $body .= '</select></span><span class="field-description">Default category applied to new articles.</span></label>';
    }
    $body .= '</div>';

    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
    foreach ($knowledgeStatusLabels as $value => $label) {
        $selected = $value === $knowledgeDefaultStatus ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
    foreach ($knowledgeVisibilityOptions as $value => $label) {
        $selected = $value === $knowledgeDefaultVisibility ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Author</span><span class="field-control"><select name="author_user_id">';
    $body .= '<option value="">No author</option>';
    foreach ($users as $user) {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }
        $username = $user['username'] ?? ('User #' . $userId);
        $body .= '<option value="' . $userId . '">' . htmlspecialchars($username) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '</div>';

    $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><textarea name="summary" rows="2" placeholder="Short overview"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Content</span><span class="field-control"><textarea name="content" rows="6" placeholder="Describe the steps, references, or templates."></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Tags</span><span class="field-control"><input type="text" name="tags" placeholder="onboarding, policies"></span></label>';
    $body .= '<label class="field"><span class="field-label">Attachments</span><span class="field-control"><textarea name="attachments" rows="3" placeholder="Local links or uploads"></textarea></span></label>';

    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Add article</button>';
    $body .= '</div>';
    $body .= '</form>';
    $body .= '</article>';

    $body .= '</section>';

    $featureRequestStatusLabels = [];
    $featureRequestStatusCounts = [];
    foreach ($featureRequestStatusOptions as $statusOption) {
        $featureRequestStatusLabels[$statusOption] = ucwords(str_replace('_', ' ', $statusOption));
        $featureRequestStatusCounts[$statusOption] = 0;
    }
    $featureRequestPriorityLabels = [];
    foreach ($featureRequestPriorityOptions as $priorityOption) {
        $featureRequestPriorityLabels[$priorityOption] = ucwords(str_replace('_', ' ', $priorityOption));
    }
    $featureRequestEntries = [];
    $featureRequestTotalVotes = 0;
    $statusRank = [];
    foreach ($featureRequestStatusOptions as $index => $statusOption) {
        $statusRank[$statusOption] = $index;
    }

    foreach ($featureRequestRecords as $request) {
        if (!is_array($request)) {
            continue;
        }

        $status = strtolower((string) ($request['status'] ?? $featureRequestStatusOptions[0]));
        if (!isset($featureRequestStatusLabels[$status])) {
            $featureRequestStatusLabels[$status] = ucwords(str_replace('_', ' ', $status));
            $featureRequestStatusCounts[$status] = 0;
            $statusRank[$status] = count($statusRank);
        }
        $featureRequestStatusCounts[$status] = ($featureRequestStatusCounts[$status] ?? 0) + 1;

        $priority = strtolower((string) ($request['priority'] ?? $featureRequestPriorityOptions[0]));
        if (!isset($featureRequestPriorityLabels[$priority])) {
            $featureRequestPriorityLabels[$priority] = ucwords(str_replace('_', ' ', $priority));
        }

        $supporters = $request['supporters'] ?? [];
        if (!is_array($supporters)) {
            $supporters = [];
        }
        $supporters = array_values(array_unique(array_filter(array_map('intval', $supporters), static function ($value) {
            return $value > 0;
        })));
        $voteCount = (int) ($request['vote_count'] ?? count($supporters));
        if ($voteCount < count($supporters)) {
            $voteCount = count($supporters);
        }
        $featureRequestTotalVotes += $voteCount;

        $linksValue = '';
        if (!empty($request['reference_links']) && is_array($request['reference_links'])) {
            $linksValue = implode("\n", array_map(static function ($link) {
                return (string) $link;
            }, $request['reference_links']));
        }

        $tagsValue = '';
        if (!empty($request['tags']) && is_array($request['tags'])) {
            $tagsValue = implode(', ', array_map(static function ($tag) {
                return (string) $tag;
            }, $request['tags']));
        }

        $featureRequestEntries[] = array_merge($request, [
            'status' => $status,
            'priority' => $priority,
            'supporters' => $supporters,
            'vote_count' => $voteCount,
            'links_value' => $linksValue,
            'tags_value' => $tagsValue,
            'supporters_input' => implode("\n", array_map('strval', $supporters)),
        ]);
    }

    if (!empty($featureRequestEntries)) {
        usort($featureRequestEntries, static function (array $a, array $b) use ($statusRank) {
            $rankA = $statusRank[$a['status'] ?? ''] ?? PHP_INT_MAX;
            $rankB = $statusRank[$b['status'] ?? ''] ?? PHP_INT_MAX;
            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            $timeA = strtotime((string) ($a['updated_at'] ?? $a['created_at'] ?? 'now'));
            $timeB = strtotime((string) ($b['updated_at'] ?? $b['created_at'] ?? 'now'));
            return $timeB <=> $timeA;
        });
    }

    $visibilityOptions = ['public' => 'Public', 'members' => 'Members', 'private' => 'Private'];

    $body .= '<section class="feature-request-manager">';
    $body .= '<h2>Feature request catalogue</h2>';
    $body .= '<p>Review and triage community ideas, adjust their visibility, and delegate ownership without editing datasets manually.</p>';

    if ($featureRequestPolicy === 'disabled') {
        $body .= '<p class="notice muted">Member submissions are currently disabled. Administrators can still create and manage feature requests from this dashboard.</p>';
    } elseif ($featureRequestPolicy === 'admins') {
        $body .= '<p class="notice muted">Only administrators may submit new requests while this policy is active.</p>';
    } elseif ($featureRequestPolicy === 'moderators') {
        $body .= '<p class="notice muted">Administrators and moderators may submit new requests. Members can follow along here.</p>';
    }

    if (!empty($featureRequestEntries)) {
        $body .= '<div class="feature-request-summary">';
        foreach ($featureRequestStatusLabels as $statusKey => $label) {
            $count = (int) ($featureRequestStatusCounts[$statusKey] ?? 0);
            $body .= '<article class="feature-request-chip feature-request-status-' . htmlspecialchars($statusKey) . '">';
            $body .= '<h3>' . htmlspecialchars($label) . '</h3>';
            $body .= '<p class="feature-request-total">' . $count . ' ' . ($count === 1 ? 'entry' : 'entries') . '</p>';
            $body .= '</article>';
        }
        $body .= '<article class="feature-request-chip feature-request-votes">';
        $body .= '<h3>Total support</h3>';
        $body .= '<p class="feature-request-total">' . $featureRequestTotalVotes . '</p>';
        $body .= '</article>';
        $body .= '</div>';
    } else {
        $body .= '<p class="notice muted">No feature requests logged yet. Use the form below to capture your first idea.</p>';
    }

    foreach ($featureRequestEntries as $request) {
        $status = (string) ($request['status'] ?? 'open');
        $priority = (string) ($request['priority'] ?? 'medium');
        $title = trim((string) ($request['title'] ?? 'Untitled request'));
        $summary = trim((string) ($request['summary'] ?? ''));
        $details = trim((string) ($request['details'] ?? ''));
        $visibility = strtolower((string) ($request['visibility'] ?? $featureRequestDefaultVisibility));
        if (!isset($visibilityOptions[$visibility])) {
            $visibility = $featureRequestDefaultVisibility;
        }
        $impact = (int) ($request['impact'] ?? 0);
        $effort = (int) ($request['effort'] ?? 0);
        $ownerRole = (string) ($request['owner_role'] ?? '');
        $ownerUserId = $request['owner_user_id'] ?? null;
        $requestorUserId = $request['requestor_user_id'] ?? null;

        $supportersInput = $request['supporters_input'] ?? '';
        $tagsValue = $request['tags_value'] ?? '';
        $linksValue = $request['links_value'] ?? '';

        $body .= '<article class="feature-request-admin-card">';
        $body .= '<header class="feature-request-admin-header">';
        $body .= '<h3>' . htmlspecialchars($title) . '</h3>';
        $body .= '<p class="feature-request-admin-meta">Status: ' . htmlspecialchars($featureRequestStatusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)))
            . ' · Priority: ' . htmlspecialchars($featureRequestPriorityLabels[$priority] ?? ucwords(str_replace('_', ' ', $priority)))
            . ' · Supporters: ' . (int) ($request['vote_count'] ?? 0) . '</p>';
        if ($summary !== '') {
            $body .= '<p class="feature-request-admin-summary">' . htmlspecialchars($summary) . '</p>';
        }
        $body .= '</header>';

        $body .= '<form method="post" action="/setup.php" class="feature-request-form">';
        $body .= '<input type="hidden" name="action" value="update_feature_request">';
        $body .= '<input type="hidden" name="feature_request_id" value="' . (int) ($request['id'] ?? 0) . '">';
        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value="' . htmlspecialchars($title) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
        foreach ($featureRequestStatusLabels as $value => $label) {
            $selected = $status === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Priority</span><span class="field-control"><select name="priority">';
        foreach ($featureRequestPriorityLabels as $value => $label) {
            $selected = $priority === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '</div>';

        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
        foreach ($visibilityOptions as $value => $label) {
            $selected = $visibility === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Impact (1-5)</span><span class="field-control"><input type="number" name="impact" min="1" max="5" value="' . $impact . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Effort (1-5)</span><span class="field-control"><input type="number" name="effort" min="1" max="5" value="' . $effort . '"></span></label>';
        $body .= '</div>';

        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Owner role</span><span class="field-control"><select name="owner_role">';
        $body .= '<option value="">Unassigned</option>';
        foreach ($roles as $roleKey => $roleDescription) {
            $selected = $ownerRole === (string) $roleKey ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars((string) $roleKey) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $roleKey)) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Owner profile</span><span class="field-control"><select name="owner_user_id">';
        $body .= '<option value="">Unassigned</option>';
        foreach ($users as $user) {
            $userId = (int) ($user['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $selected = ($ownerUserId !== null && (int) $ownerUserId === $userId) ? ' selected' : '';
            $username = $user['username'] ?? ('User #' . $userId);
            $body .= '<option value="' . $userId . '"' . $selected . '>' . htmlspecialchars($username) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Requestor</span><span class="field-control"><select name="requestor_user_id">';
        $body .= '<option value="">Unassigned</option>';
        foreach ($users as $user) {
            $userId = (int) ($user['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $selected = ($requestorUserId !== null && (int) $requestorUserId === $userId) ? ' selected' : '';
            $username = $user['username'] ?? ('User #' . $userId);
            $body .= '<option value="' . $userId . '"' . $selected . '>' . htmlspecialchars($username) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '</div>';

        $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><textarea name="summary" rows="2">' . htmlspecialchars($summary) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Details</span><span class="field-control"><textarea name="details" rows="4">' . htmlspecialchars($details) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Tags</span><span class="field-control"><input type="text" name="tags" value="' . htmlspecialchars($tagsValue) . '"></span><span class="field-description">Comma-separated keywords for filtering.</span></label>';
        $body .= '<label class="field"><span class="field-label">Reference links</span><span class="field-control"><textarea name="reference_links" rows="3" placeholder="One URL or path per line">' . htmlspecialchars($linksValue) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Supporter IDs</span><span class="field-control"><textarea name="supporters" rows="3" placeholder="One user ID per line">' . htmlspecialchars($supportersInput) . '</textarea></span><span class="field-description">Use numeric profile IDs to prefill acknowledgement lists.</span></label>';
        $body .= '<label class="field"><span class="field-label">Admin notes</span><span class="field-control"><textarea name="admin_notes" rows="2">' . htmlspecialchars((string) ($request['admin_notes'] ?? '')) . '</textarea></span></label>';

        $body .= '<div class="action-row">';
        $body .= '<button type="submit" class="button primary">Save changes</button>';
        $body .= '</div>';
        $body .= '</form>';

        $body .= '<form method="post" action="/setup.php" class="feature-request-delete-form" onsubmit="return confirm(\'Delete this feature request?\');">';
        $body .= '<input type="hidden" name="action" value="delete_feature_request">';
        $body .= '<input type="hidden" name="feature_request_id" value="' . (int) ($request['id'] ?? 0) . '">';
        $body .= '<button type="submit" class="button danger">Delete request</button>';
        $body .= '</form>';

        $body .= '</article>';
    }

    $defaultStatus = $featureRequestStatusOptions[0] ?? 'open';
    $defaultPriority = $featureRequestPriorityOptions[0] ?? 'medium';

    $body .= '<article class="feature-request-admin-card feature-request-create">';
    $body .= '<header><h3>Create new feature request</h3><p>Capture a new idea or administrative task and classify it before publishing to members.</p></header>';
    $body .= '<form method="post" action="/setup.php" class="feature-request-form">';
    $body .= '<input type="hidden" name="action" value="create_feature_request">';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
    foreach ($featureRequestStatusLabels as $value => $label) {
        $selected = $value === $defaultStatus ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Priority</span><span class="field-control"><select name="priority">';
    foreach ($featureRequestPriorityLabels as $value => $label) {
        $selected = $value === $defaultPriority ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '</div>';

    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
    foreach ($visibilityOptions as $value => $label) {
        $selected = $value === $featureRequestDefaultVisibility ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Impact (1-5)</span><span class="field-control"><input type="number" name="impact" min="1" max="5" value="3"></span></label>';
    $body .= '<label class="field"><span class="field-label">Effort (1-5)</span><span class="field-control"><input type="number" name="effort" min="1" max="5" value="3"></span></label>';
    $body .= '</div>';

    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Owner role</span><span class="field-control"><select name="owner_role">';
    $body .= '<option value="">Unassigned</option>';
    foreach ($roles as $roleKey => $roleDescription) {
        $body .= '<option value="' . htmlspecialchars((string) $roleKey) . '">' . htmlspecialchars(ucfirst((string) $roleKey)) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Owner profile</span><span class="field-control"><select name="owner_user_id">';
    $body .= '<option value="">Unassigned</option>';
    foreach ($users as $user) {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }
        $username = $user['username'] ?? ('User #' . $userId);
        $body .= '<option value="' . $userId . '">' . htmlspecialchars($username) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Requestor</span><span class="field-control"><select name="requestor_user_id">';
    $body .= '<option value="">Unassigned</option>';
    foreach ($users as $user) {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }
        $username = $user['username'] ?? ('User #' . $userId);
        $body .= '<option value="' . $userId . '">' . htmlspecialchars($username) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '</div>';

    $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><textarea name="summary" rows="2"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Details</span><span class="field-control"><textarea name="details" rows="4"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Tags</span><span class="field-control"><input type="text" name="tags" placeholder="design, automation"></span></label>';
    $body .= '<label class="field"><span class="field-label">Reference links</span><span class="field-control"><textarea name="reference_links" rows="3" placeholder="One URL or path per line"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Supporter IDs</span><span class="field-control"><textarea name="supporters" rows="3" placeholder="Optional numeric IDs for initial supporters"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Admin notes</span><span class="field-control"><textarea name="admin_notes" rows="2"></textarea></span></label>';

    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Create feature request</button>';
    $body .= '</div>';
    $body .= '</form>';
    $body .= '</article>';

    $body .= '</section>';

    $body .= '<section class="bug-report-manager" id="bug-report-manager">';
    $body .= '<h2>Bug report triage</h2>';
    $body .= '<p>Audit locally filed bugs, adjust ownership, and coordinate fixes without leaving the Filegate setup dashboard.</p>';

    if ($bugPolicySetting === 'disabled') {
        $body .= '<p class="notice muted">Members cannot submit new bug reports while this policy is disabled. Administrators can still seed and maintain records here.</p>';
    } elseif ($bugPolicySetting === 'admins') {
        $body .= '<p class="notice muted">Only administrators may submit new bug reports at the moment.</p>';
    } elseif ($bugPolicySetting === 'moderators') {
        $body .= '<p class="notice muted">Moderators and administrators may submit new bug reports. Members can follow along once published.</p>';
    }

    if (!empty($bugEntries)) {
        $body .= '<div class="bug-report-summary">';
        foreach ($bugStatusLabels as $statusKey => $label) {
            $count = (int) ($bugStatusCounts[$statusKey] ?? 0);
            $body .= '<article class="bug-report-chip bug-status-' . htmlspecialchars($statusKey) . '">';
            $body .= '<h3>' . htmlspecialchars($label) . '</h3>';
            $body .= '<p class="bug-report-total">' . $count . ' ' . ($count === 1 ? 'bug' : 'bugs') . '</p>';
            $body .= '</article>';
        }
        $body .= '<article class="bug-report-chip bug-watchers">';
        $body .= '<h3>Total watchers</h3>';
        $body .= '<p class="bug-report-total">' . $bugTotalWatchers . '</p>';
        $body .= '</article>';
        $body .= '</div>';
    } else {
        $body .= '<p class="notice muted">No bug reports recorded yet. Use the form below to document the first issue.</p>';
    }

    foreach ($bugEntries as $bug) {
        $bugId = (int) ($bug['id'] ?? 0);
        $title = trim((string) ($bug['title'] ?? 'Untitled bug'));
        $summary = trim((string) ($bug['summary'] ?? ''));
        $details = trim((string) ($bug['details'] ?? ''));
        $environment = trim((string) ($bug['environment'] ?? ''));
        $resolutionNotes = trim((string) ($bug['resolution_notes'] ?? ''));
        $status = (string) ($bug['status'] ?? ($bugStatusOptions[0] ?? 'new'));
        $severity = (string) ($bug['severity'] ?? ($bugSeverityOptions[0] ?? 'medium'));
        $visibility = strtolower((string) ($bug['visibility'] ?? $bugDefaultVisibility));
        if (!isset($visibilityOptions[$visibility])) {
            $visibility = $bugDefaultVisibility;
        }
        $ownerRole = (string) ($bug['owner_role'] ?? '');
        $ownerUserId = $bug['owner_user_id'] ?? null;
        $reporterUserId = $bug['reporter_user_id'] ?? null;
        $watcherCount = count($bug['watchers'] ?? []);
        $tagsValue = $bug['tags_value'] ?? '';
        $stepsValue = $bug['steps_value'] ?? '';
        $versionsValue = $bug['versions_value'] ?? '';
        $linksValue = $bug['links_value'] ?? '';
        $attachmentsValue = $bug['attachments_value'] ?? '';
        $watchersInput = $bug['watchers_input'] ?? '';
        $createdLabel = $bug['created_at_label'] ?? '';
        $updatedLabel = $bug['updated_at_label'] ?? '';
        $lastActivityLabel = $bug['last_activity_label'] ?? '';

        $body .= '<article class="bug-report-admin-card" id="bug-report-' . $bugId . '">';
        $body .= '<header class="bug-report-admin-header">';
        $body .= '<h3>' . htmlspecialchars($title) . '</h3>';
        $metaParts = [];
        $metaParts[] = 'Status: ' . htmlspecialchars($bugStatusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)));
        $metaParts[] = 'Severity: ' . htmlspecialchars($bugSeverityLabels[$severity] ?? ucwords(str_replace('_', ' ', $severity)));
        $metaParts[] = 'Watchers: ' . $watcherCount;
        if ($lastActivityLabel !== '') {
            $metaParts[] = 'Last activity ' . htmlspecialchars($lastActivityLabel);
        }
        $body .= '<p class="bug-report-admin-meta">' . implode(' · ', $metaParts) . '</p>';
        if ($summary !== '') {
            $body .= '<p class="bug-report-admin-summary">' . htmlspecialchars($summary) . '</p>';
        }
        if ($createdLabel !== '' || $updatedLabel !== '') {
            $body .= '<p class="bug-report-admin-timestamps">';
            if ($createdLabel !== '') {
                $body .= 'Created ' . htmlspecialchars($createdLabel);
            }
            if ($updatedLabel !== '') {
                $body .= ' · Updated ' . htmlspecialchars($updatedLabel);
            }
            $body .= '</p>';
        }
        $body .= '</header>';

        $body .= '<form method="post" action="/setup.php" class="bug-report-form">';
        $body .= '<input type="hidden" name="action" value="update_bug_report">';
        $body .= '<input type="hidden" name="bug_report_id" value="' . $bugId . '">';
        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value="' . htmlspecialchars($title) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
        foreach ($bugStatusLabels as $value => $label) {
            $selected = $status === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Severity</span><span class="field-control"><select name="severity">';
        foreach ($bugSeverityLabels as $value => $label) {
            $selected = $severity === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '</div>';

        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
        foreach ($visibilityOptions as $value => $label) {
            $selected = $visibility === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Owner role</span><span class="field-control"><select name="owner_role">';
        $body .= '<option value="">Unassigned</option>';
        foreach ($roles as $roleKey => $roleDescription) {
            $selected = $ownerRole === (string) $roleKey ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars((string) $roleKey) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $roleKey)) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Owner profile</span><span class="field-control"><select name="owner_user_id">';
        $body .= '<option value="">Unassigned</option>';
        foreach ($users as $user) {
            $userId = (int) ($user['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $selected = ($ownerUserId !== null && (int) $ownerUserId === $userId) ? ' selected' : '';
            $username = $user['username'] ?? ('User #' . $userId);
            $body .= '<option value="' . $userId . '"' . $selected . '>' . htmlspecialchars($username) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Reporter</span><span class="field-control"><select name="reporter_user_id">';
        $body .= '<option value="">Unassigned</option>';
        foreach ($users as $user) {
            $userId = (int) ($user['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $selected = ($reporterUserId !== null && (int) $reporterUserId === $userId) ? ' selected' : '';
            $username = $user['username'] ?? ('User #' . $userId);
            $body .= '<option value="' . $userId . '"' . $selected . '>' . htmlspecialchars($username) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '</div>';

        $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><textarea name="summary" rows="2">' . htmlspecialchars($summary) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Details</span><span class="field-control"><textarea name="details" rows="4">' . htmlspecialchars($details) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Environment</span><span class="field-control"><input type="text" name="environment" value="' . htmlspecialchars($environment) . '" placeholder="Browser and operating system"></span></label>';
        $body .= '<label class="field"><span class="field-label">Resolution notes</span><span class="field-control"><textarea name="resolution_notes" rows="2">' . htmlspecialchars($resolutionNotes) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Steps to reproduce</span><span class="field-control"><textarea name="steps_to_reproduce" rows="3" placeholder="One step per line">' . htmlspecialchars($stepsValue) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Affected versions</span><span class="field-control"><textarea name="affected_versions" rows="2" placeholder="One version per line">' . htmlspecialchars($versionsValue) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Tags</span><span class="field-control"><input type="text" name="tags" value="' . htmlspecialchars($tagsValue) . '" placeholder="ui, uploads"></span></label>';
        $body .= '<label class="field"><span class="field-label">Reference links</span><span class="field-control"><textarea name="reference_links" rows="3" placeholder="One URL or path per line">' . htmlspecialchars($linksValue) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Attachment references</span><span class="field-control"><textarea name="attachments" rows="2" placeholder="Optional upload identifiers or paths">' . htmlspecialchars($attachmentsValue) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Watcher IDs</span><span class="field-control"><textarea name="watchers" rows="2" placeholder="Numeric profile IDs, one per line">' . htmlspecialchars($watchersInput) . '</textarea></span></label>';

        $body .= '<div class="action-row">';
        $body .= '<button type="submit" class="button primary">Save bug</button>';
        $body .= '</div>';
        $body .= '</form>';

        $body .= '<form method="post" action="/setup.php" class="bug-report-delete-form" onsubmit="return confirm(\'Delete this bug report?\');">';
        $body .= '<input type="hidden" name="action" value="delete_bug_report">';
        $body .= '<input type="hidden" name="bug_report_id" value="' . $bugId . '">';
        $body .= '<button type="submit" class="button danger">Delete bug report</button>';
        $body .= '</form>';

        $body .= '</article>';
    }

    $defaultBugStatus = $bugStatusOptions[0] ?? 'new';
    $defaultBugSeverity = $bugSeverityOptions[0] ?? 'medium';

    $body .= '<article class="bug-report-admin-card bug-report-create">';
    $body .= '<header><h3>Log new bug report</h3><p>Document a new bug, attach reproduction steps, and assign an owner without touching JSON files.</p></header>';
    $body .= '<form method="post" action="/setup.php" class="bug-report-form">';
    $body .= '<input type="hidden" name="action" value="create_bug_report">';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
    foreach ($bugStatusLabels as $value => $label) {
        $selected = $value === $defaultBugStatus ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Severity</span><span class="field-control"><select name="severity">';
    foreach ($bugSeverityLabels as $value => $label) {
        $selected = $value === $defaultBugSeverity ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '</div>';

    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
    foreach ($visibilityOptions as $value => $label) {
        $selected = $value === $bugDefaultVisibility ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Owner role</span><span class="field-control"><select name="owner_role">';
    $body .= '<option value="">Unassigned</option>';
    foreach ($roles as $roleKey => $roleDescription) {
        $selected = $bugDefaultOwnerRole !== '' && $bugDefaultOwnerRole === (string) $roleKey ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars((string) $roleKey) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $roleKey)) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Owner profile</span><span class="field-control"><select name="owner_user_id">';
    $body .= '<option value="">Unassigned</option>';
    foreach ($users as $user) {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }
        $username = $user['username'] ?? ('User #' . $userId);
        $body .= '<option value="' . $userId . '">' . htmlspecialchars($username) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '</div>';

    $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><textarea name="summary" rows="2"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Details</span><span class="field-control"><textarea name="details" rows="4"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Environment</span><span class="field-control"><input type="text" name="environment" placeholder="Browser and operating system"></span></label>';
    $body .= '<label class="field"><span class="field-label">Resolution notes</span><span class="field-control"><textarea name="resolution_notes" rows="2"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Steps to reproduce</span><span class="field-control"><textarea name="steps_to_reproduce" rows="3" placeholder="One step per line"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Affected versions</span><span class="field-control"><textarea name="affected_versions" rows="2" placeholder="One version per line"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Tags</span><span class="field-control"><input type="text" name="tags" placeholder="ui, uploads"></span></label>';
    $body .= '<label class="field"><span class="field-label">Reference links</span><span class="field-control"><textarea name="reference_links" rows="3" placeholder="One URL or path per line"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Attachment references</span><span class="field-control"><textarea name="attachments" rows="2" placeholder="Optional upload identifiers or paths"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Watcher IDs</span><span class="field-control"><textarea name="watchers" rows="2" placeholder="Numeric profile IDs, one per line"></textarea></span></label>';

    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Create bug report</button>';
    $body .= '</div>';
    $body .= '</form>';
    $body .= '</article>';

    $body .= '</section>';

    $body .= '<section class="event-manager" id="event-manager">';
    $body .= '<h2>Event planning</h2>';
    $body .= '<p>Curate upcoming sessions, workshops, and community gatherings without editing JSON files. Configure visibility, hosts, RSVP policies, and supporting metadata directly from this dashboard.</p>';

    if ($eventPolicySetting === 'disabled') {
        $body .= '<p class="notice muted">Event creation is currently disabled for members. Administrators can still seed events here.</p>';
    } elseif ($eventPolicySetting === 'admins') {
        $body .= '<p class="notice muted">Only administrators can create new events while this policy is active.</p>';
    } elseif ($eventPolicySetting === 'moderators') {
        $body .= '<p class="notice muted">Administrators and moderators can schedule events. Members can only view published listings.</p>';
    }

    if (!empty($eventStatusCounts)) {
        $body .= '<div class="event-summary">';
        foreach ($eventStatusCounts as $statusKey => $count) {
            $label = $eventStatusLabels[$statusKey] ?? ucwords(str_replace('_', ' ', $statusKey));
            $body .= '<article class="poll-chip event-status-' . htmlspecialchars($statusKey) . '">';
            $body .= '<h3>' . htmlspecialchars($label) . '</h3>';
            $body .= '<p class="poll-total">' . $count . ' ' . ($count === 1 ? 'event' : 'events') . '</p>';
            $body .= '</article>';
        }
        $body .= '<article class="poll-chip event-upcoming">';
        $body .= '<h3>Upcoming</h3>';
        $body .= '<p class="poll-total">' . $eventUpcomingCount . '</p>';
        $body .= '</article>';
        $body .= '<article class="poll-chip event-past">';
        $body .= '<h3>Past</h3>';
        $body .= '<p class="poll-total">' . $eventPastCount . '</p>';
        $body .= '</article>';
        if ($eventTotalRsvps > 0 || $eventTotalCapacity > 0) {
            $body .= '<article class="poll-chip event-rsvp">';
            $body .= '<h3>RSVPs</h3>';
            $capacityLabel = $eventTotalCapacity > 0 ? ' / ' . $eventTotalCapacity : '';
            $body .= '<p class="poll-total">' . $eventTotalRsvps . $capacityLabel . '</p>';
            $body .= '</article>';
        }
        $body .= '</div>';
    }

    $body .= '<article class="event-create-card">';
    $body .= '<h3>Schedule a new event</h3>';
    $body .= '<p>Provide a title, timing, and optional RSVP controls. All fields can be adjusted later.</p>';
    $body .= '<form method="post" action="/setup.php" class="event-form">';
    $body .= '<input type="hidden" name="action" value="create_event">';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" required></span></label>';
    $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
    foreach ($eventStatusLabels as $value => $label) {
        $body .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
    foreach ($eventVisibilityLabels as $value => $label) {
        $selected = $value === $eventDefaultVisibility ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '</div>';
    $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><textarea name="summary" rows="2"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="4"></textarea></span></label>';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Start</span><span class="field-control"><input type="datetime-local" name="start_at" value="' . htmlspecialchars(date('Y-m-d\TH:i', $nowTimestamp + 86400)) . '"></span></label>';
    $body .= '<label class="field"><span class="field-label">End</span><span class="field-control"><input type="datetime-local" name="end_at" value="' . htmlspecialchars(date('Y-m-d\TH:i', $nowTimestamp + 90000)) . '"></span></label>';
    $body .= '<label class="field"><span class="field-label">Timezone</span><span class="field-control"><input type="text" name="timezone" value="' . htmlspecialchars($eventDefaultTimezone) . '"></span></label>';
    $body .= '</div>';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Location</span><span class="field-control"><input type="text" name="location" placeholder="Main hall or virtual space"></span></label>';
    $body .= '<label class="field"><span class="field-label">Location link</span><span class="field-control"><input type="url" name="location_url" placeholder="https://"></span></label>';
    $body .= '</div>';
    $body .= '<fieldset class="fieldset">';
    $body .= '<legend>RSVP options</legend>';
    $body .= '<label class="field checkbox-field"><input type="checkbox" name="allow_rsvp" value="1"> Enable RSVP tracking</label>';
    $body .= '<label class="field"><span class="field-label">RSVP policy</span><span class="field-control"><select name="rsvp_policy">';
    $rsvpOptions = ['public' => 'Public', 'members' => 'Members', 'private' => 'Private'];
    foreach ($rsvpOptions as $value => $label) {
        $selected = $value === $eventRsvpPolicySetting ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">RSVP limit</span><span class="field-control"><input type="number" name="rsvp_limit" min="0" placeholder="0 for unlimited"></span></label>';
    $body .= '</fieldset>';
    $body .= '<label class="field"><span class="field-label">Host IDs</span><span class="field-control"><textarea name="hosts" rows="2" placeholder="Numeric profile IDs"></textarea></span><span class="field-description">Provide one ID per line or comma separated. Defaults to the submitting admin.</span></label>';
    $body .= '<label class="field"><span class="field-label">Collaborator IDs</span><span class="field-control"><textarea name="collaborators" rows="2" placeholder="Numeric profile IDs"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Tags</span><span class="field-control"><textarea name="tags" rows="2" placeholder="workshop, onboarding"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Attachment references</span><span class="field-control"><textarea name="attachments" rows="2" placeholder="Optional upload identifiers"></textarea></span></label>';
    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Create event</button>';
    $body .= '</div>';
    $body .= '</form>';
    $body .= '</article>';

    if (!empty($eventEntries)) {
        foreach ($eventEntries as $event) {
            $eventId = (int) ($event['id'] ?? 0);
            $title = trim((string) ($event['title'] ?? 'Untitled event'));
            $summary = trim((string) ($event['summary'] ?? ''));
            $description = trim((string) ($event['description'] ?? ''));
            $status = $event['status'] ?? ($eventStatusOptions[0] ?? 'draft');
            $visibility = $event['visibility'] ?? $eventDefaultVisibility;
            $allowRsvp = !empty($event['allow_rsvp']);
            $rsvpPolicy = strtolower((string) ($event['rsvp_policy'] ?? $eventRsvpPolicySetting));
            if (!isset($rsvpOptions[$rsvpPolicy])) {
                $rsvpPolicy = $eventRsvpPolicySetting;
            }
            $rsvpLimitValue = $event['rsvp_limit'];
            $hostList = $event['hosts_labels'] ?? [];
            $collaboratorList = $event['collaborators_labels'] ?? [];
            $body .= '<article class="event-admin-card" id="event-' . $eventId . '">';
            $body .= '<header class="event-admin-header">';
            $body .= '<h3>' . htmlspecialchars($title) . '</h3>';
            $metaParts = [];
            $metaParts[] = 'Status: ' . htmlspecialchars($eventStatusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)));
            $metaParts[] = 'Visibility: ' . htmlspecialchars($eventVisibilityLabels[$visibility] ?? ucfirst($visibility));
            $metaParts[] = 'Starts ' . htmlspecialchars($event['start_label'] ?? '');
            $metaParts[] = 'Ends ' . htmlspecialchars($event['end_label'] ?? '');
            if ($allowRsvp) {
                $capacity = ($rsvpLimitValue ?? 0) > 0 ? ' / ' . (int) $rsvpLimitValue : '';
                $metaParts[] = 'RSVPs: ' . count($event['rsvps'] ?? []) . $capacity;
            }
            $body .= '<p class="event-meta">' . implode(' · ', array_filter($metaParts)) . '</p>';
            if (!empty($hostList)) {
                $body .= '<p class="event-meta-subtle">Hosts: ' . htmlspecialchars(implode(', ', $hostList)) . '</p>';
            }
            if (!empty($collaboratorList)) {
                $body .= '<p class="event-meta-subtle">Collaborators: ' . htmlspecialchars(implode(', ', $collaboratorList)) . '</p>';
            }
            $body .= '</header>';

            if ($summary !== '') {
                $body .= '<p class="event-summary-text">' . htmlspecialchars($summary) . '</p>';
            }
            if ($description !== '') {
                $body .= '<details class="event-description"><summary>View description</summary><p>' . nl2br(htmlspecialchars($description)) . '</p></details>';
            }

            $body .= '<form method="post" action="/setup.php" class="event-form">';
            $body .= '<input type="hidden" name="action" value="update_event">';
            $body .= '<input type="hidden" name="event_id" value="' . $eventId . '">';
            $body .= '<div class="field-grid">';
            $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value="' . htmlspecialchars($title) . '"></span></label>';
            $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
            foreach ($eventStatusLabels as $value => $label) {
                $selected = $status === $value ? ' selected' : '';
                $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
            }
            $body .= '</select></span></label>';
            $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
            foreach ($eventVisibilityLabels as $value => $label) {
                $selected = $visibility === $value ? ' selected' : '';
                $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
            }
            $body .= '</select></span></label>';
            $body .= '</div>';
            $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><textarea name="summary" rows="2">' . htmlspecialchars($summary) . '</textarea></span></label>';
            $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="4">' . htmlspecialchars($description) . '</textarea></span></label>';
            $body .= '<div class="field-grid">';
            $body .= '<label class="field"><span class="field-label">Start</span><span class="field-control"><input type="datetime-local" name="start_at" value="' . htmlspecialchars($event['start_input'] ?? '') . '"></span></label>';
            $body .= '<label class="field"><span class="field-label">End</span><span class="field-control"><input type="datetime-local" name="end_at" value="' . htmlspecialchars($event['end_input'] ?? '') . '"></span></label>';
            $body .= '<label class="field"><span class="field-label">Timezone</span><span class="field-control"><input type="text" name="timezone" value="' . htmlspecialchars($event['timezone'] ?? $eventDefaultTimezone) . '"></span></label>';
            $body .= '</div>';
            $body .= '<div class="field-grid">';
            $body .= '<label class="field"><span class="field-label">Location</span><span class="field-control"><input type="text" name="location" value="' . htmlspecialchars($event['location'] ?? '') . '"></span></label>';
            $body .= '<label class="field"><span class="field-label">Location link</span><span class="field-control"><input type="url" name="location_url" value="' . htmlspecialchars($event['location_url'] ?? '') . '"></span></label>';
            $body .= '</div>';
            $checkedRsvp = $allowRsvp ? ' checked' : '';
            $body .= '<fieldset class="fieldset">';
            $body .= '<legend>RSVP options</legend>';
            $body .= '<label class="field checkbox-field"><input type="checkbox" name="allow_rsvp" value="1"' . $checkedRsvp . '> Enable RSVP tracking</label>';
            $body .= '<label class="field"><span class="field-label">RSVP policy</span><span class="field-control"><select name="rsvp_policy">';
            foreach ($rsvpOptions as $value => $label) {
                $selected = $rsvpPolicy === $value ? ' selected' : '';
                $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
            }
            $body .= '</select></span></label>';
            $body .= '<label class="field"><span class="field-label">RSVP limit</span><span class="field-control"><input type="number" name="rsvp_limit" min="0" value="' . ($rsvpLimitValue !== null ? (int) $rsvpLimitValue : '') . '" placeholder="0 for unlimited"></span></label>';
            $body .= '</fieldset>';
            $body .= '<label class="field"><span class="field-label">Host IDs</span><span class="field-control"><textarea name="hosts" rows="2">' . htmlspecialchars($event['hosts_input'] ?? '') . '</textarea></span></label>';
            $body .= '<label class="field"><span class="field-label">Collaborator IDs</span><span class="field-control"><textarea name="collaborators" rows="2">' . htmlspecialchars($event['collaborators_input'] ?? '') . '</textarea></span></label>';
            $body .= '<label class="field"><span class="field-label">Tags</span><span class="field-control"><textarea name="tags" rows="2">' . htmlspecialchars($event['tags_input'] ?? '') . '</textarea></span></label>';
            $body .= '<label class="field"><span class="field-label">Attachment references</span><span class="field-control"><textarea name="attachments" rows="2">' . htmlspecialchars($event['attachments_input'] ?? '') . '</textarea></span></label>';
            $body .= '<div class="action-row">';
            $body .= '<button type="submit" class="button primary">Update event</button>';
            $body .= '</div>';
            $body .= '</form>';
            $body .= '<form method="post" action="/setup.php" class="inline-form event-delete-form" onsubmit="return confirm(\'Delete this event?\');">';
            $body .= '<input type="hidden" name="action" value="delete_event">';
            $body .= '<input type="hidden" name="event_id" value="' . $eventId . '">';
            $body .= '<button type="submit" class="button danger">Delete event</button>';
            $body .= '</form>';
            $body .= '</article>';
        }
    } else {
        $body .= '<p class="notice muted">No events are scheduled yet. Use the form above to plan the first gathering.</p>';
    }

    $body .= '</section>';

    $body .= '<section class="poll-manager">';
    $body .= '<h2>Poll catalogue</h2>';
    $body .= '<p>Create and moderate community polls without touching JSON files. Options, votes, and visibility are all stored locally.</p>';

    if ($pollPolicySetting === 'disabled') {
        $body .= '<p class="notice muted">Poll creation is disabled for members. Administrators can still seed and update polls here.</p>';
    } elseif ($pollPolicySetting === 'admins') {
        $body .= '<p class="notice muted">Only administrators can create new polls while this policy is active.</p>';
    } elseif ($pollPolicySetting === 'moderators') {
        $body .= '<p class="notice muted">Administrators and moderators can create polls. Members can only participate.</p>';
    }

    if (!empty($pollEntries)) {
        $body .= '<div class="poll-summary">';
        foreach ($pollStatusLabels as $statusKey => $label) {
            $count = (int) ($pollStatusCounts[$statusKey] ?? 0);
            $body .= '<article class="poll-chip poll-status-' . htmlspecialchars($statusKey) . '">';
            $body .= '<h3>' . htmlspecialchars($label) . '</h3>';
            $body .= '<p class="poll-total">' . $count . ' ' . ($count === 1 ? 'poll' : 'polls') . '</p>';
            $body .= '</article>';
        }
        $body .= '<article class="poll-chip poll-responses">';
        $body .= '<h3>Total responses</h3>';
        $body .= '<p class="poll-total">' . $pollTotalResponses . '</p>';
        $body .= '</article>';
        $body .= '</div>';
    } else {
        $body .= '<p class="notice muted">No polls recorded yet. Use the form below to create the first one.</p>';
    }

    foreach ($pollEntries as $poll) {
        $pollId = (int) ($poll['id'] ?? 0);
        $question = trim((string) ($poll['question'] ?? 'Untitled poll'));
        $description = trim((string) ($poll['description'] ?? ''));
        $status = strtolower((string) ($poll['status'] ?? ($pollStatusOptions[0] ?? 'draft')));
        $visibility = strtolower((string) ($poll['visibility'] ?? $pollDefaultVisibility));
        if (!isset($visibilityOptions[$visibility])) {
            $visibility = $pollDefaultVisibility;
        }
        $allowMultiple = !empty($poll['allow_multiple']);
        $maxSelections = (int) ($poll['max_selections'] ?? ($allowMultiple ? 0 : 1));
        if ($maxSelections < 0) {
            $maxSelections = 0;
        }
        $totalResponses = (int) ($poll['total_responses'] ?? 0);
        if ($totalResponses < 0) {
            $totalResponses = 0;
        }
        $totalVotes = (int) ($poll['total_votes'] ?? 0);
        if ($totalVotes < 0) {
            $totalVotes = 0;
        }
        $options = $poll['options'] ?? [];
        $optionsTextarea = [];
        foreach ($options as $option) {
            $optionsTextarea[] = $option['label'] ?? '';
        }
        $optionsTextareaValue = trim(implode("\n", $optionsTextarea));
        $expiresAt = trim((string) ($poll['expires_at'] ?? ''));
        $expiresAtValue = '';
        $expiresAtLabel = '';
        if ($expiresAt !== '') {
            $timestamp = strtotime($expiresAt);
            if ($timestamp !== false) {
                $expiresAtValue = date('Y-m-d\TH:i', $timestamp);
                $expiresAtLabel = date('M j, Y H:i', $timestamp);
            }
        }
        $updatedAt = trim((string) ($poll['updated_at'] ?? $poll['created_at'] ?? ''));
        $updatedAtLabel = '';
        if ($updatedAt !== '') {
            $updatedTimestamp = strtotime($updatedAt);
            if ($updatedTimestamp !== false) {
                $updatedAtLabel = date('M j, Y H:i', $updatedTimestamp);
            }
        }
        $ownerRole = trim((string) ($poll['owner_role'] ?? ''));
        $ownerUserId = $poll['owner_user_id'] ?? null;

        $body .= '<article class="poll-admin-card" id="poll-' . $pollId . '">';
        $body .= '<header class="poll-admin-header">';
        $body .= '<h3>' . htmlspecialchars($question) . '</h3>';
        $metaParts = [];
        $metaParts[] = 'Status: ' . htmlspecialchars($pollStatusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)));
        $metaParts[] = 'Visibility: ' . htmlspecialchars($visibilityOptions[$visibility] ?? ucfirst($visibility));
        $metaParts[] = $allowMultiple ? 'Multiple selections enabled' : 'Single selection';
        $metaParts[] = 'Responses: ' . $totalResponses;
        $metaParts[] = 'Votes: ' . $totalVotes;
        $body .= '<p class="poll-meta">' . implode(' · ', $metaParts) . '</p>';
        if ($expiresAtLabel !== '') {
            $body .= '<p class="poll-meta-subtle">Closes ' . htmlspecialchars($expiresAtLabel) . '</p>';
        }
        if ($updatedAtLabel !== '') {
            $body .= '<p class="poll-meta-subtle">Last updated ' . htmlspecialchars($updatedAtLabel) . '</p>';
        }
        $body .= '</header>';

        if ($description !== '') {
            $body .= '<p class="poll-description">' . htmlspecialchars($description) . '</p>';
        }

        if (!empty($options)) {
            $body .= '<ul class="poll-option-list">';
            foreach ($options as $option) {
                $label = $option['label'] ?? '';
                $voteCount = (int) ($option['vote_count'] ?? 0);
                $supporterCount = (int) ($option['supporter_count'] ?? count($option['supporters'] ?? []));
                $body .= '<li><span class="poll-option-label">' . htmlspecialchars($label) . '</span><span class="poll-option-count">' . $voteCount . ' ' . ($voteCount === 1 ? 'vote' : 'votes') . '</span>';
                if ($supporterCount !== $voteCount) {
                    $body .= '<span class="poll-option-supporters">' . $supporterCount . ' supporters</span>';
                }
                $body .= '</li>';
            }
            $body .= '</ul>';
        }

        $body .= '<form method="post" action="/setup.php" class="poll-form">';
        $body .= '<input type="hidden" name="action" value="update_poll">';
        $body .= '<input type="hidden" name="poll_id" value="' . $pollId . '">';
        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Question</span><span class="field-control"><input type="text" name="question" value="' . htmlspecialchars($question) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
        foreach ($pollStatusLabels as $value => $label) {
            $selected = $status === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
        foreach ($visibilityOptions as $value => $label) {
            $selected = $visibility === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $checkedMultiple = $allowMultiple ? ' checked' : '';
        $body .= '<label class="field checkbox-field"><input type="checkbox" name="allow_multiple" value="1"' . $checkedMultiple . '> Allow multiple selections</label>';
        $body .= '<label class="field"><span class="field-label">Maximum selections</span><span class="field-control"><input type="number" name="max_selections" min="0" value="' . htmlspecialchars((string) $maxSelections) . '"></span><span class="field-description">Set to 0 for unlimited selections when multiple answers are allowed.</span></label>';
        $body .= '</div>';
        $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="2">' . htmlspecialchars($description) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Options</span><span class="field-control"><textarea name="options" rows="4" placeholder="One option per line">' . htmlspecialchars($optionsTextareaValue) . '</textarea></span></label>';
        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Expires at</span><span class="field-control"><input type="datetime-local" name="expires_at" value="' . htmlspecialchars($expiresAtValue) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Owner role</span><span class="field-control"><input type="text" name="owner_role" value="' . htmlspecialchars($ownerRole) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Owner user ID</span><span class="field-control"><input type="number" name="owner_user_id" value="' . ($ownerUserId !== null ? (int) $ownerUserId : '') . '" min="0"></span></label>';
        $body .= '</div>';
        $body .= '<div class="action-row">';
        $body .= '<button type="submit" class="button primary">Update poll</button>';
        $body .= '</div>';
        $body .= '</form>';
        $body .= '<form method="post" action="/setup.php" class="inline-form poll-delete-form" onsubmit="return confirm(\'Delete this poll?\');">';
        $body .= '<input type="hidden" name="action" value="delete_poll">';
        $body .= '<input type="hidden" name="poll_id" value="' . $pollId . '">';
        $body .= '<button type="submit" class="button danger">Delete poll</button>';
        $body .= '</form>';
        $body .= '</article>';
    }

    $body .= '<article class="poll-card poll-create">';
    $body .= '<h3>Create poll</h3>';
    $body .= '<form method="post" action="/setup.php" class="poll-form">';
    $body .= '<input type="hidden" name="action" value="create_poll">';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Question</span><span class="field-control"><input type="text" name="question" required></span></label>';
    $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
    foreach ($pollStatusLabels as $value => $label) {
        $body .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
    foreach ($visibilityOptions as $value => $label) {
        $selected = $value === $pollDefaultVisibility ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $checkedDefaultMultiple = $pollAllowMultipleDefault ? ' checked' : '';
    $body .= '<label class="field checkbox-field"><input type="checkbox" name="allow_multiple" value="1"' . $checkedDefaultMultiple . '> Allow multiple selections</label>';
    $defaultMax = $pollAllowMultipleDefault ? 0 : 1;
    $body .= '<label class="field"><span class="field-label">Maximum selections</span><span class="field-control"><input type="number" name="max_selections" min="0" value="' . $defaultMax . '"></span><span class="field-description">Set to 0 for unlimited selections when multiple answers are allowed.</span></label>';
    $body .= '</div>';
    $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="2"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Options</span><span class="field-control"><textarea name="options" rows="4" placeholder="First option\nSecond option"></textarea></span></label>';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Expires at</span><span class="field-control"><input type="datetime-local" name="expires_at"></span></label>';
    $body .= '<label class="field"><span class="field-label">Owner role</span><span class="field-control"><input type="text" name="owner_role" placeholder="admin"></span></label>';
    $body .= '<label class="field"><span class="field-label">Owner user ID</span><span class="field-control"><input type="number" name="owner_user_id" min="0"></span></label>';
    $body .= '</div>';
    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Create poll</button>';
    $body .= '</div>';
    $body .= '</form>';
    $body .= '</article>';
    $body .= '</section>';

    $body .= '<section class="automation-manager">';
    $body .= '<h2>Automation rules</h2>';
    $body .= '<p>Define local-first workflows that react to Filegate events without relying on remote services.</p>';

    if (!empty($automationStatusLabels) || !empty($automationTriggerLabels) || !empty($automationConditionTypes) || !empty($automationActionTypes)) {
        $body .= '<details class="automation-legend" open>';
        $body .= '<summary>Automation reference guide</summary>';
        $body .= '<div class="automation-legend-grid">';

        if (!empty($automationStatusLabels)) {
            $body .= '<section class="automation-legend-column">';
            $body .= '<h3>Status options</h3>';
            $body .= '<ul>';
            foreach ($automationStatusLabels as $statusKey => $label) {
                $body .= '<li><code>' . htmlspecialchars((string) $statusKey) . '</code> – ' . htmlspecialchars($label) . '</li>';
            }
            $body .= '</ul>';
            $body .= '</section>';
        }

        if (!empty($automationTriggerLabels)) {
            $body .= '<section class="automation-legend-column">';
            $body .= '<h3>Triggers</h3>';
            $body .= '<ul>';
            foreach ($automationTriggerLabels as $triggerKey => $triggerLabel) {
                $body .= '<li><code>' . htmlspecialchars((string) $triggerKey) . '</code> – ' . htmlspecialchars($triggerLabel) . '</li>';
            }
            $body .= '</ul>';
            $body .= '</section>';
        }

        if (!empty($automationConditionTypes)) {
            $body .= '<section class="automation-legend-column">';
            $body .= '<h3>Condition types</h3>';
            $body .= '<ul>';
            foreach ($automationConditionTypes as $conditionType) {
                $body .= '<li><code>' . htmlspecialchars((string) $conditionType) . '</code> – ' . htmlspecialchars(ucwords(str_replace('_', ' ', (string) $conditionType))) . '</li>';
            }
            $body .= '</ul>';
            $body .= '</section>';
        }

        if (!empty($automationActionTypes)) {
            $body .= '<section class="automation-legend-column">';
            $body .= '<h3>Action types</h3>';
            $body .= '<ul>';
            foreach ($automationActionTypes as $actionType) {
                $body .= '<li><code>' . htmlspecialchars((string) $actionType) . '</code> – ' . htmlspecialchars(ucwords(str_replace('_', ' ', (string) $actionType))) . '</li>';
            }
            $body .= '</ul>';
            $body .= '</section>';
        }

        $body .= '</div>';
        $body .= '<p class="automation-legend-note">Format each rule as <code>type|key=value</code>; multiple key-value pairs can be separated with commas.</p>';
        $body .= '</details>';
    }

    if (!empty($automationEntries)) {
        $body .= '<div class="automation-summary">';
        foreach ($automationStatusLabels as $statusKey => $label) {
            $count = (int) ($automationStatusCounts[$statusKey] ?? 0);
            $statusClass = $automationStatusClass((string) $statusKey);
            $body .= '<article class="automation-chip automation-status-' . htmlspecialchars($statusClass) . '">';
            $body .= '<h3>' . htmlspecialchars($label) . '</h3>';
            $body .= '<p class="automation-total">' . $count . ' ' . ($count === 1 ? 'rule' : 'rules') . '</p>';
            $body .= '</article>';
        }
        $body .= '<article class="automation-chip automation-active">';
        $body .= '<h3>Active</h3>';
        $body .= '<p class="automation-total">' . $automationActiveCount . '</p>';
        $body .= '</article>';
        $body .= '<article class="automation-chip automation-run-metric">';
        $body .= '<h3>Total runs</h3>';
        $body .= '<p class="automation-total">' . $automationTotalRuns . '</p>';
        $body .= '</article>';
        $body .= '</div>';
    } else {
        $body .= '<p class="notice muted">No automations recorded yet. Use the form below to generate your first rule.</p>';
    }

    $conditionTypeSummary = implode(', ', array_map('strval', $automationConditionTypes));
    $actionTypeSummary = implode(', ', array_map('strval', $automationActionTypes));

    foreach ($automationEntries as $automation) {
        $automationId = (int) ($automation['id'] ?? 0);
        $name = trim((string) ($automation['name'] ?? 'Untitled automation'));
        $description = trim((string) ($automation['description'] ?? ''));
        $status = (string) ($automation['status'] ?? $automationDefaultStatus);
        $statusLabel = $automationStatusLabels[$status] ?? ucwords(str_replace('_', ' ', $status));
        $statusClass = $automationStatusClass($status);
        $trigger = strtolower((string) ($automation['trigger'] ?? ($automationTriggerOptions[0] ?? 'user_registered')));
        if (!isset($automationTriggerLabels[$trigger])) {
            $trigger = $automationTriggerOptions[0] ?? 'user_registered';
        }
        $priority = strtolower((string) ($automation['priority'] ?? ($automationPriorityOptions[0] ?? 'medium')));
        if (!isset($automationPriorityLabels[$priority])) {
            $priority = $automationPriorityOptions[0] ?? 'medium';
        }
        $priorityLabel = $automationPriorityLabels[$priority] ?? ucwords(str_replace('_', ' ', $priority));
        $runCount = (int) ($automation['run_count'] ?? 0);
        if ($runCount < 0) {
            $runCount = 0;
        }
        $runLimit = $automation['run_limit'] ?? null;
        $runLimitValue = $runLimit === null ? '' : (int) $runLimit;
        $lastRunAt = trim((string) ($automation['last_run_at'] ?? ''));
        $lastRunLabel = '';
        if ($lastRunAt !== '') {
            $lastRunTimestamp = strtotime($lastRunAt);
            if ($lastRunTimestamp !== false) {
                $lastRunLabel = date('M j, Y H:i', $lastRunTimestamp);
            }
        }
        $tags = $automation['tags'] ?? [];
        if (!is_array($tags)) {
            $tags = [];
        }
        $tagsValue = implode(', ', array_map('strval', $tags));
        $tagList = array_values(array_filter(array_map('strval', $tags), static function ($value) {
            return trim($value) !== '';
        }));
        $ownerRole = trim((string) ($automation['owner_role'] ?? ''));
        $ownerUserId = $automation['owner_user_id'] ?? null;
        $conditionsLines = $automation['conditions_lines'] ?? '';
        $actionsLines = $automation['actions_lines'] ?? '';
        $conditionsList = $automation['conditions_list'] ?? [];
        $actionsList = $automation['actions_list'] ?? [];
        $updatedAt = trim((string) ($automation['updated_at'] ?? $automation['created_at'] ?? ''));
        $updatedLabel = '';
        if ($updatedAt !== '') {
            $updatedTimestamp = strtotime($updatedAt);
            if ($updatedTimestamp !== false) {
                $updatedLabel = date('M j, Y H:i', $updatedTimestamp);
            }
        }

        $body .= '<article class="automation-card automation-status-' . htmlspecialchars($statusClass) . '" id="automation-' . $automationId . '">';
        $body .= '<header class="automation-card-header">';
        $body .= '<div class="automation-card-title">';
        $body .= '<h3>' . htmlspecialchars($name) . '</h3>';
        $body .= '<span class="automation-status-pill">' . htmlspecialchars($statusLabel) . '</span>';
        $body .= '</div>';
        $metaParts = [];
        $metaParts[] = 'Status: ' . htmlspecialchars($statusLabel);
        $metaParts[] = 'Trigger: ' . htmlspecialchars($automationTriggerLabels[$trigger] ?? ucwords(str_replace('_', ' ', $trigger)));
        $metaParts[] = 'Priority: ' . htmlspecialchars($priorityLabel);
        $metaParts[] = 'Runs: ' . $runCount;
        if ($runLimitValue !== '') {
            $metaParts[] = 'Limit: ' . $runLimitValue;
        }
        if ($lastRunLabel !== '') {
            $metaParts[] = 'Last run ' . htmlspecialchars($lastRunLabel);
        }
        $body .= '<p class="automation-meta">' . implode(' · ', $metaParts) . '</p>';
        if ($updatedLabel !== '') {
            $body .= '<p class="automation-meta-subtle">Updated ' . htmlspecialchars($updatedLabel) . '</p>';
        }
        $body .= '<p class="automation-meta-subtle">Automation #' . $automationId . '</p>';
        if ($description !== '') {
            $body .= '<p class="automation-description">' . htmlspecialchars($description) . '</p>';
        }
        if (!empty($tagList)) {
            $body .= '<ul class="automation-tag-list">';
            foreach ($tagList as $tag) {
                $body .= '<li><span>' . htmlspecialchars($tag) . '</span></li>';
            }
            $body .= '</ul>';
        }
        $body .= '</header>';

        $body .= '<div class="automation-details">';
        $body .= '<div class="automation-column">';
        $body .= '<h4>Conditions</h4>';
        if (!empty($conditionsList)) {
            $body .= '<ul class="automation-rule-list">';
            foreach ($conditionsList as $item) {
                $body .= '<li>' . $item . '</li>';
            }
            $body .= '</ul>';
        } else {
            $body .= '<p class="automation-empty-rules">Runs for every matching trigger.</p>';
        }
        $body .= '</div>';
        $body .= '<div class="automation-column">';
        $body .= '<h4>Actions</h4>';
        if (!empty($actionsList)) {
            $body .= '<ul class="automation-rule-list">';
            foreach ($actionsList as $item) {
                $body .= '<li>' . $item . '</li>';
            }
            $body .= '</ul>';
        } else {
            $body .= '<p class="automation-empty-rules">No actions parsed.</p>';
        }
        $body .= '</div>';
        $body .= '</div>';

        $body .= '<form method="post" action="/setup.php" class="automation-form">';
        $body .= '<input type="hidden" name="action" value="update_automation">';
        $body .= '<input type="hidden" name="automation_id" value="' . $automationId . '">';
        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Name</span><span class="field-control"><input type="text" name="name" value="' . htmlspecialchars($name) . '" required></span></label>';
        $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
        foreach ($automationStatusLabels as $value => $label) {
            $selected = $status === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Trigger</span><span class="field-control"><select name="trigger">';
        foreach ($automationTriggerLabels as $value => $label) {
            $selected = $trigger === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Priority</span><span class="field-control"><select name="priority">';
        foreach ($automationPriorityLabels as $value => $label) {
            $selected = $priority === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '</div>';

        $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="2">' . htmlspecialchars($description) . '</textarea></span></label>';

        $body .= '<details class="automation-advanced">';
        $body .= '<summary>Advanced controls</summary>';
        $body .= '<div class="field-grid automation-advanced-grid">';
        $body .= '<label class="field"><span class="field-label">Run limit</span><span class="field-control"><input type="number" name="run_limit" min="0" value="' . htmlspecialchars((string) $runLimitValue) . '"></span><span class="field-description">Leave blank for unlimited runs.</span></label>';
        $body .= '<label class="field"><span class="field-label">Run count</span><span class="field-control"><input type="number" name="run_count" min="0" value="' . $runCount . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Last run timestamp</span><span class="field-control"><input type="text" name="last_run_at" value="' . htmlspecialchars($lastRunAt) . '" placeholder="2024-01-01T00:00:00+00:00"></span></label>';
        $body .= '</div>';

        $body .= '<div class="field-grid automation-advanced-grid">';
        $body .= '<label class="field"><span class="field-label">Owner role</span><span class="field-control"><select name="owner_role">';
        $body .= '<option value="">Unassigned</option>';
        foreach ($roles as $roleKey => $roleDescription) {
            $roleValue = (string) $roleKey;
            $selected = $ownerRole !== '' && $ownerRole === $roleValue ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($roleValue) . '"' . $selected . '>' . htmlspecialchars(ucfirst($roleValue)) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Owner profile</span><span class="field-control"><select name="owner_user_id">';
        $body .= '<option value="">Unassigned</option>';
        foreach ($users as $user) {
            $userId = (int) ($user['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $username = $user['username'] ?? ('User #' . $userId);
            $selected = ($ownerUserId !== null && (int) $ownerUserId === $userId) ? ' selected' : '';
            $body .= '<option value="' . $userId . '"' . $selected . '>' . htmlspecialchars($username) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '</div>';
        $body .= '</details>';

        $body .= '<label class="field"><span class="field-label">Tags</span><span class="field-control"><input type="text" name="tags" value="' . htmlspecialchars($tagsValue) . '" placeholder="onboarding, welcome"></span></label>';

        $body .= '<label class="field"><span class="field-label">Conditions</span><span class="field-control"><textarea name="conditions" rows="3" placeholder="role_equals|role=member">' . htmlspecialchars($conditionsLines) . '</textarea></span><span class="field-description">One rule per line. Allowed types: ' . htmlspecialchars($conditionTypeSummary) . '.</span></label>';
        $body .= '<label class="field"><span class="field-label">Actions</span><span class="field-control"><textarea name="actions" rows="3" placeholder="enqueue_notification|channel=email,template=post_update" required>' . htmlspecialchars($actionsLines) . '</textarea></span><span class="field-description">One action per line. Allowed types: ' . htmlspecialchars($actionTypeSummary) . '.</span></label>';

        $body .= '<div class="action-row">';
        $body .= '<button type="submit" class="button primary">Update automation</button>';
        $body .= '</div>';
        $body .= '</form>';

        $body .= '<form method="post" action="/setup.php" class="inline-form automation-delete-form" onsubmit="return confirm(\'Delete this automation?\');">';
        $body .= '<input type="hidden" name="action" value="delete_automation">';
        $body .= '<input type="hidden" name="automation_id" value="' . $automationId . '">';
        $body .= '<button type="submit" class="button danger">Delete automation</button>';
        $body .= '</form>';

        $body .= '</article>';
    }

    $body .= '<article class="automation-card automation-create">';
    $body .= '<header class="automation-card-header">';
    $body .= '<h3>Create automation</h3>';
    $body .= '<p class="automation-description">Launch a new workflow with triggers, optional conditions, and one or more actions.</p>';
    $body .= '</header>';
    $body .= '<form method="post" action="/setup.php" class="automation-form">';
    $body .= '<input type="hidden" name="action" value="create_automation">';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Name</span><span class="field-control"><input type="text" name="name" required></span></label>';
    $body .= '<label class="field"><span class="field-label">Status</span><span class="field-control"><select name="status">';
    foreach ($automationStatusLabels as $value => $label) {
        $selected = $value === $automationDefaultStatus ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Trigger</span><span class="field-control"><select name="trigger">';
    foreach ($automationTriggerLabels as $value => $label) {
        $body .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Priority</span><span class="field-control"><select name="priority">';
    foreach ($automationPriorityLabels as $value => $label) {
        $selected = $value === ($automationPriorityOptions[0] ?? 'medium') ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '</div>';

    $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="2" placeholder="Describe what this automation does"></textarea></span></label>';

    $body .= '<details class="automation-advanced">';
    $body .= '<summary>Advanced controls</summary>';
    $body .= '<div class="field-grid automation-advanced-grid">';
    $body .= '<label class="field"><span class="field-label">Run limit</span><span class="field-control"><input type="number" name="run_limit" min="0" placeholder="Leave blank"></span><span class="field-description">Leave blank for unlimited runs.</span></label>';
    $body .= '<label class="field"><span class="field-label">Owner role</span><span class="field-control"><select name="owner_role">';
    $body .= '<option value="">Unassigned</option>';
    foreach ($roles as $roleKey => $roleDescription) {
        $roleValue = (string) $roleKey;
        $selected = $roleValue === $automationDefaultOwnerRole ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($roleValue) . '"' . $selected . '>' . htmlspecialchars(ucfirst($roleValue)) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Owner profile</span><span class="field-control"><select name="owner_user_id">';
    $body .= '<option value="">Unassigned</option>';
    foreach ($users as $user) {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }
        $username = $user['username'] ?? ('User #' . $userId);
        $body .= '<option value="' . $userId . '">' . htmlspecialchars($username) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '</div>';
    $body .= '</details>';

    $body .= '<label class="field"><span class="field-label">Tags</span><span class="field-control"><input type="text" name="tags" placeholder="onboarding, alerts"></span><span class="field-description">Separate with commas or new lines.</span></label>';
    $body .= '<label class="field"><span class="field-label">Conditions</span><span class="field-control"><textarea name="conditions" rows="3" placeholder="role_equals|role=member"></textarea></span><span class="field-description">One condition per line. Allowed types: ' . htmlspecialchars($conditionTypeSummary) . '. Leave blank to run for every trigger.</span></label>';
    $body .= '<label class="field"><span class="field-label">Actions</span><span class="field-control"><textarea name="actions" rows="3" placeholder="enqueue_notification|channel=email,template=post_update" required></textarea></span><span class="field-description">One action per line. Allowed types: ' . htmlspecialchars($actionTypeSummary) . '.</span></label>';

    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Create automation</button>';
    $body .= '</div>';
    $body .= '</form>';
    $body .= '</article>';

    $body .= '</section>';

    $changelogList = [];
    $changelogTypeCounts = [];
    $highlightCount = 0;
    foreach ($changelogRecords as $record) {
        if (!is_array($record)) {
            continue;
        }

        $typeKey = strtolower((string) ($record['type'] ?? 'announcement'));
        $changelogTypeCounts[$typeKey] = ($changelogTypeCounts[$typeKey] ?? 0) + 1;
        if (!empty($record['highlight'])) {
            $highlightCount++;
        }

        $tags = $record['tags'] ?? [];
        if (!is_array($tags)) {
            $tags = [];
        }
        $links = $record['links'] ?? [];
        if (!is_array($links)) {
            $links = [];
        }
        $related = $record['related_project_status_ids'] ?? [];
        if (!is_array($related)) {
            $related = [];
        }

        $publishedAt = $record['published_at'] ?? '';
        $publishedTimestamp = null;
        if ($publishedAt !== '') {
            $parsed = strtotime((string) $publishedAt);
            if ($parsed !== false) {
                $publishedTimestamp = $parsed;
            }
        }
        $publishedDisplay = $publishedTimestamp ? date('M j, Y H:i', $publishedTimestamp) : 'Not published';
        $publishedInput = $publishedTimestamp ? date('Y-m-d\TH:i', $publishedTimestamp) : '';

        $changelogList[] = [
            'raw' => $record,
            'id' => (int) ($record['id'] ?? 0),
            'title' => trim((string) ($record['title'] ?? 'Untitled update')),
            'summary' => trim((string) ($record['summary'] ?? '')),
            'type' => $typeKey,
            'visibility' => strtolower((string) ($record['visibility'] ?? 'public')),
            'highlight' => !empty($record['highlight']),
            'body' => trim((string) ($record['body'] ?? '')),
            'tags' => $tags,
            'links' => $links,
            'related' => $related,
            'published_display' => $publishedDisplay,
            'published_input' => $publishedInput,
            'created_at' => $record['created_at'] ?? '',
            'updated_at' => $record['updated_at'] ?? '',
            'published_timestamp' => $publishedTimestamp ?? 0,
        ];
    }

    if (!empty($changelogList)) {
        usort($changelogList, static function (array $a, array $b) {
            return ($b['published_timestamp'] ?? 0) <=> ($a['published_timestamp'] ?? 0);
        });
    }

    $typeLabels = [
        'release' => 'Release',
        'improvement' => 'Improvement',
        'fix' => 'Fix',
        'announcement' => 'Announcement',
        'breaking' => 'Breaking change',
    ];
    $visibilityLabels = [
        'public' => 'Public',
        'members' => 'Members',
        'private' => 'Administrators',
    ];

    $body .= '<section class="changelog-manager">';
    $body .= '<h2>Changelog</h2>';
    $body .= '<p>Publish release notes, template updates, and dataset changes without editing files. Highlight key updates and control visibility per entry.</p>';

    if (empty($changelogList)) {
        $body .= '<p class="notice muted">No changelog entries recorded yet. Start logging releases so profiles can follow what changed.</p>';
    } else {
        $body .= '<div class="changelog-summary">';
        foreach ($changelogTypeCounts as $typeKey => $count) {
            $label = $typeLabels[$typeKey] ?? ucwords(str_replace('_', ' ', $typeKey));
            $body .= '<article class="changelog-chip">';
            $body .= '<h3>' . htmlspecialchars($label) . '</h3>';
            $body .= '<p class="changelog-total">' . (int) $count . ' ' . ($count === 1 ? 'entry' : 'entries') . '</p>';
            $body .= '</article>';
        }
        $body .= '<article class="changelog-chip">';
        $body .= '<h3>Highlights</h3>';
        $body .= '<p class="changelog-total">' . (int) $highlightCount . '</p>';
        $body .= '</article>';
        $body .= '</div>';
    }

    foreach ($changelogList as $entry) {
        $record = $entry['raw'];
        $tagsValue = implode(', ', $entry['tags']);
        $linksValue = implode("\n", $entry['links']);
        $relatedValue = implode(', ', array_map(static function ($value) {
            return (string) $value;
        }, $entry['related']));

        $body .= '<article class="changelog-card">';
        $body .= '<header>';
        $body .= '<h3>' . htmlspecialchars($entry['title']) . '</h3>';
        $body .= '<p class="changelog-meta">Type: ' . htmlspecialchars($typeLabels[$entry['type']] ?? ucfirst($entry['type'])) . ' · Visibility: ' . htmlspecialchars($visibilityLabels[$entry['visibility']] ?? ucfirst($entry['visibility'])) . ' · Published: ' . htmlspecialchars($entry['published_display']) . '</p>';
        if (!empty($entry['updated_at'])) {
            $updatedTimestamp = strtotime((string) $entry['updated_at']);
            if ($updatedTimestamp) {
                $body .= '<p class="changelog-updated">Last updated ' . htmlspecialchars(date('M j, Y H:i', $updatedTimestamp)) . '</p>';
            }
        }
        $body .= '</header>';

        $body .= '<form method="post" action="/setup.php" class="changelog-form">';
        $body .= '<input type="hidden" name="action" value="update_changelog_entry">';
        $body .= '<input type="hidden" name="changelog_id" value="' . (int) $entry['id'] . '">';
        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value="' . htmlspecialchars($entry['title']) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Type</span><span class="field-control"><select name="type">';
        foreach ($typeLabels as $value => $label) {
            $selected = $entry['type'] === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
        foreach ($visibilityLabels as $value => $label) {
            $selected = $entry['visibility'] === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '</div>';

        $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><textarea name="summary" rows="3">' . htmlspecialchars($entry['summary']) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Details</span><span class="field-control"><textarea name="body" rows="4" placeholder="Extended notes and embeds supported by the composer.">' . htmlspecialchars($entry['body']) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Tags</span><span class="field-control"><input type="text" name="tags" value="' . htmlspecialchars($tagsValue) . '" placeholder="release, accessibility"></span><span class="field-description">Comma-separated keywords used by the feed and notifications.</span></label>';
        $body .= '<label class="field"><span class="field-label">Links</span><span class="field-control"><textarea name="links" rows="3" placeholder="One link per line">' . htmlspecialchars($linksValue) . '</textarea></span></label>';
        $body .= '<label class="field"><span class="field-label">Related roadmap IDs</span><span class="field-control"><input type="text" name="related_project_status_ids" value="' . htmlspecialchars($relatedValue) . '" placeholder="1, 2"></span><span class="field-description">Reference roadmap entries that shipped with this change.</span></label>';

        $checked = $entry['highlight'] ? ' checked' : '';
        $body .= '<label class="field checkbox-field"><span class="field-control"><input type="checkbox" name="highlight" value="1"' . $checked . '> Highlight this entry</span><span class="field-description">Highlighted entries surface prominently in the feed.</span></label>';

        $body .= '<label class="field"><span class="field-label">Published at</span><span class="field-control"><input type="datetime-local" name="published_at" value="' . htmlspecialchars($entry['published_input']) . '"></span><span class="field-description">Leave blank to keep unpublished or set a new timestamp.</span></label>';

        $body .= '<div class="action-row">';
        $body .= '<button type="submit" class="button primary">Save changelog entry</button>';
        $body .= '</div>';
        $body .= '</form>';

        $body .= '<form method="post" action="/setup.php" class="changelog-delete-form" onsubmit="return confirm(\'Delete this changelog entry?\');">';
        $body .= '<input type="hidden" name="action" value="delete_changelog_entry">';
        $body .= '<input type="hidden" name="changelog_id" value="' . (int) $entry['id'] . '">';
        $body .= '<button type="submit" class="button danger">Delete entry</button>';
        $body .= '</form>';

        $body .= '</article>';
    }

    $body .= '<article class="changelog-card create">';
    $body .= '<header><h3>Create changelog entry</h3><p>Announce a release, fix, or configuration update with full visibility and highlight controls.</p></header>';
    $body .= '<form method="post" action="/setup.php" class="changelog-form">';
    $body .= '<input type="hidden" name="action" value="create_changelog_entry">';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Type</span><span class="field-control"><select name="type">';
    foreach ($typeLabels as $value => $label) {
        $selected = $value === 'release' ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
    foreach ($visibilityLabels as $value => $label) {
        $body .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '</div>';

    $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><textarea name="summary" rows="3" placeholder="Short description shown in the feed."></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Details</span><span class="field-control"><textarea name="body" rows="4" placeholder="Full body content. Supports HTML5 embeds and upload previews."></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Tags</span><span class="field-control"><input type="text" name="tags" placeholder="release, performance"></span></label>';
    $body .= '<label class="field"><span class="field-label">Links</span><span class="field-control"><textarea name="links" rows="3" placeholder="One link per line"></textarea></span></label>';
    $body .= '<label class="field"><span class="field-label">Related roadmap IDs</span><span class="field-control"><input type="text" name="related_project_status_ids" placeholder="1, 2"></span></label>';
    $body .= '<label class="field checkbox-field"><span class="field-control"><input type="checkbox" name="highlight" value="1"> Highlight this entry</span></label>';
    $body .= '<label class="field"><span class="field-label">Published at</span><span class="field-control"><input type="datetime-local" name="published_at"></span></label>';

    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Create changelog entry</button>';
    $body .= '</div>';
    $body .= '</form>';
    $body .= '</article>';

    $body .= '</section>';

    if (!empty($datasets)) {
        $body .= '<section class="dataset-manager">';
        $body .= '<h2>Dataset Management</h2>';
        $body .= '<p>Review and regenerate the local datasets that power Filegate. Upload replacements or edit the payloads directly without leaving the browser.</p>';

        foreach ($datasets as $dataset) {
            $datasetName = $dataset['name'] ?? '';
            $label = $dataset['label'] ?? $datasetName;
            $description = $dataset['description'] ?? '';
            $nature = $dataset['nature'] ?? 'dynamic';
            $format = $dataset['format'] ?? 'json';
            $size = $dataset['size'] ?? '0 B';
            $modified = $dataset['modified'] ?? '';
            $payload = $dataset['payload'] ?? '';
            $rows = (int) ($dataset['rows'] ?? 12);
            $editable = !empty($dataset['editable']);
            $missing = !empty($dataset['missing']);
            $hasDefaults = !empty($dataset['has_defaults']);
            $snapshots = $dataset['snapshots'] ?? [];
            $snapshotLimit = (int) ($dataset['snapshot_limit'] ?? 0);

            $detailsAttributes = 'class="dataset-card" data-dataset="' . htmlspecialchars($datasetName) . '" data-nature="' . htmlspecialchars($nature) . '" data-format="' . htmlspecialchars($format) . '"';
            if ($missing) {
                $detailsAttributes .= ' open';
            }

            $body .= '<details ' . $detailsAttributes . '>';
            $body .= '<summary>';
            $body .= '<span class="dataset-label">' . htmlspecialchars($label) . '</span>';
            $body .= '<span class="dataset-meta">';
            $body .= '<span class="dataset-chip">Nature: ' . htmlspecialchars(ucfirst($nature)) . '</span>';
            $body .= '<span class="dataset-chip">Format: ' . htmlspecialchars(strtoupper($format)) . '</span>';
            $body .= '<span class="dataset-chip">Size: ' . htmlspecialchars($size) . '</span>';
            $body .= '<span class="dataset-chip">Updated: ' . htmlspecialchars($modified) . '</span>';
            if ($missing) {
                $body .= '<span class="dataset-chip warning">Not generated</span>';
            }
            $body .= '</span>';
            $body .= '</summary>';

            $body .= '<div class="dataset-body">';
            if ($description !== '') {
                $body .= '<p class="dataset-description">' . htmlspecialchars($description) . '</p>';
            }

            if (!$editable) {
                $body .= '<p class="notice muted">This dataset is currently read-only because the target path is not writable. Adjust filesystem permissions to modify it from the browser.</p>';
            }

            $body .= '<form method="post" action="/setup.php" class="dataset-form" enctype="multipart/form-data">';
            $body .= '<input type="hidden" name="action" value="save_dataset">';
            $body .= '<input type="hidden" name="dataset" value="' . htmlspecialchars($datasetName) . '">';
            $textareaAttributes = 'name="dataset_payload" rows="' . $rows . '"';
            if (!$editable) {
                $textareaAttributes .= ' readonly';
            }
            $body .= '<label class="field">';
            $body .= '<span class="field-label">Dataset payload</span>';
            $body .= '<span class="field-control"><textarea ' . $textareaAttributes . '>' . htmlspecialchars($payload) . '</textarea></span>';
            $body .= '<span class="field-description">Paste or edit the dataset contents directly. For XML datasets, provide complete XML markup.</span>';
            $body .= '</label>';

            $body .= '<label class="field upload-field">';
            $body .= '<span class="field-label">Upload replacement</span>';
            $fileAttributes = 'type="file" name="dataset_file" accept=".' . htmlspecialchars($format) . '"';
            if (!$editable) {
                $fileAttributes .= ' disabled';
            }
            $body .= '<span class="field-control"><input ' . $fileAttributes . '></span>';
            $body .= '<span class="field-description">Choose a local .' . htmlspecialchars($format) . ' file to replace the current dataset. Uploaded content overrides any text edits above.</span>';
            $body .= '</label>';

            $body .= '<div class="action-row">';
            if ($editable) {
                $body .= '<button type="submit" class="button primary">Save dataset</button>';
            } else {
                $body .= '<button type="submit" class="button" disabled>Save dataset</button>';
            }
            if ($hasDefaults && $editable) {
                $body .= '<button type="submit" name="action_override" value="reset_dataset" class="button danger">Reset to defaults</button>';
            }
            $body .= '</div>';
            $body .= '</form>';

            $body .= '<div class="snapshot-section">';
            $body .= '<h3>Snapshots</h3>';
            if ($snapshotLimit > 0) {
                $body .= '<p class="notice muted">Showing up to ' . htmlspecialchars((string) $snapshotLimit) . ' most recent captures for this dataset.</p>';
            }
            $body .= '<form method="post" action="/setup.php" class="snapshot-form">';
            $body .= '<input type="hidden" name="action" value="create_snapshot">';
            $body .= '<input type="hidden" name="dataset" value="' . htmlspecialchars($datasetName) . '">';
            $body .= '<label class="field compact">';
            $body .= '<span class="field-label">Snapshot label</span>';
            $body .= '<span class="field-control"><input type="text" name="snapshot_label" placeholder="Manual snapshot"></span>';
            $body .= '<span class="field-description">Provide a short label before capturing the current dataset state.</span>';
            $body .= '</label>';
            if ($editable) {
                $body .= '<button type="submit" class="button">Create snapshot</button>';
            } else {
                $body .= '<button type="submit" class="button" disabled>Create snapshot</button>';
            }
            $body .= '</form>';

            if (!empty($snapshots)) {
                $body .= '<div class="snapshot-list">';
                foreach ($snapshots as $snapshot) {
                    $snapshotId = (int) ($snapshot['id'] ?? 0);
                    $snapshotReason = $snapshot['reason'] ?? '';
                    $snapshotCreatedAt = $snapshot['created_at'] ?? '';
                    $snapshotUser = $snapshot['created_by'] ?? '';
                    $snapshotPreview = $snapshot['preview'] ?? '';
                    $snapshotFormat = strtoupper($snapshot['format'] ?? 'json');

                    $body .= '<article class="snapshot-card" data-snapshot="' . htmlspecialchars((string) $snapshotId) . '">';
                    $body .= '<header>';
                    $body .= '<span class="snapshot-reason">' . htmlspecialchars($snapshotReason === '' ? 'Snapshot #' . $snapshotId : $snapshotReason) . '</span>';
                    $body .= '<span class="snapshot-meta">';
                    if ($snapshotCreatedAt !== '') {
                        $body .= '<span class="snapshot-chip">' . htmlspecialchars($snapshotCreatedAt) . '</span>';
                    }
                    $body .= '<span class="snapshot-chip">Format: ' . htmlspecialchars($snapshotFormat) . '</span>';
                    if ($snapshotUser !== '') {
                        $body .= '<span class="snapshot-chip">Captured by ' . htmlspecialchars($snapshotUser) . '</span>';
                    }
                    $body .= '</span>';
                    $body .= '</header>';
                    $body .= '<div class="snapshot-preview">';
                    $body .= '<textarea rows="6" readonly>' . htmlspecialchars($snapshotPreview) . '</textarea>';
                    $body .= '</div>';
                    $body .= '<div class="snapshot-actions">';
                    $body .= '<form method="post" action="/setup.php" class="inline-form">';
                    $body .= '<input type="hidden" name="action" value="restore_snapshot">';
                    $body .= '<input type="hidden" name="dataset" value="' . htmlspecialchars($datasetName) . '">';
                    $body .= '<input type="hidden" name="snapshot_id" value="' . htmlspecialchars((string) $snapshotId) . '">';
                    if ($editable) {
                        $body .= '<button type="submit" class="button primary">Restore</button>';
                    } else {
                        $body .= '<button type="submit" class="button" disabled>Restore</button>';
                    }
                    $body .= '</form>';
                    $body .= '<form method="post" action="/setup.php" class="inline-form">';
                    $body .= '<input type="hidden" name="action" value="delete_snapshot">';
                    $body .= '<input type="hidden" name="dataset" value="' . htmlspecialchars($datasetName) . '">';
                    $body .= '<input type="hidden" name="snapshot_id" value="' . htmlspecialchars((string) $snapshotId) . '">';
                    if ($editable) {
                        $body .= '<button type="submit" class="button danger">Delete</button>';
                    } else {
                        $body .= '<button type="submit" class="button" disabled>Delete</button>';
                    }
                    $body .= '</form>';
                    $body .= '</div>';
                    $body .= '</article>';
                }
                $body .= '</div>';
            } else {
                $body .= '<p class="notice muted">No snapshots have been recorded yet.</p>';
            }
            $body .= '</div>';

            $body .= '</div>';
            $body .= '</details>';
        }

        $body .= '</section>';
    }

    $datasetOptions = $activityDatasetLabels;
    if (!is_array($datasetOptions)) {
        $datasetOptions = [];
    } else {
        asort($datasetOptions);
    }

    if (!is_array($activityCategories)) {
        $activityCategories = [];
    }

    if (!is_array($activityActions)) {
        $activityActions = [];
    }

    $body .= '<section class="activity-log" id="activity-log">';
    $body .= '<h2>Activity log</h2>';
    $body .= '<p>Inspect the audit trail for dataset saves, snapshot operations, and administrative actions without leaving the browser.</p>';
    $body .= '<form method="get" action="/setup.php" class="activity-filter-form">';
    $body .= '<div class="activity-filter-grid">';
    $body .= '<label class="field"><span class="field-label">Dataset</span><span class="field-control"><select name="log_dataset">';
    $body .= '<option value="">All datasets</option>';
    foreach ($datasetOptions as $datasetKey => $datasetLabel) {
        $selected = ((string) ($activityFilters['dataset'] ?? '') === (string) $datasetKey) ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars((string) $datasetKey) . '"' . $selected . '>' . htmlspecialchars($datasetLabel) . '</option>';
    }
    $body .= '</select></span></label>';

    $body .= '<label class="field"><span class="field-label">Category</span><span class="field-control"><select name="log_category">';
    $body .= '<option value="">All categories</option>';
    foreach ($activityCategories as $categoryValue) {
        $categoryValue = (string) $categoryValue;
        $selected = ((string) ($activityFilters['category'] ?? '') === $categoryValue) ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($categoryValue) . '"' . $selected . '>' . htmlspecialchars(ucfirst($categoryValue)) . '</option>';
    }
    $body .= '</select></span></label>';

    $body .= '<label class="field"><span class="field-label">Action</span><span class="field-control"><select name="log_action">';
    $body .= '<option value="">All actions</option>';
    foreach ($activityActions as $actionValue) {
        $actionValue = (string) $actionValue;
        $selected = ((string) ($activityFilters['action'] ?? '') === $actionValue) ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($actionValue) . '"' . $selected . '>' . htmlspecialchars(ucfirst($actionValue)) . '</option>';
    }
    $body .= '</select></span></label>';

    $body .= '<label class="field"><span class="field-label">User filter</span><span class="field-control"><input type="text" name="log_user" value="' . htmlspecialchars((string) ($activityFilters['user'] ?? '')) . '" placeholder="username, #id, role"></span><span class="field-description">Matches usernames, roles, and numeric identifiers.</span></label>';

    $body .= '<label class="field compact"><span class="field-label">Show</span><span class="field-control"><input type="number" name="log_limit" min="5" max="200" value="' . htmlspecialchars((string) $activityLimit) . '"></span><span class="field-description">Maximum entries to display.</span></label>';
    $body .= '</div>';
    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Apply filters</button>';
    $body .= '<a class="button" href="/setup.php#activity-log">Reset</a>';
    $body .= '</div>';
    $body .= '</form>';

    $body .= '<p class="activity-summary">Showing ' . htmlspecialchars((string) count($activityRecords)) . ' of ' . htmlspecialchars((string) $activityTotal) . ' recorded events.</p>';

    if (empty($activityRecords)) {
        $body .= '<p class="notice muted">No activity recorded for the selected filters yet.</p>';
    } else {
        $body .= '<div class="activity-entries">';
        foreach ($activityRecords as $entry) {
            $categoryLabel = (string) ($entry['category'] ?? '');
            $actionLabel = (string) ($entry['action'] ?? '');
            $datasetLabel = (string) ($entry['dataset_label'] ?? '');
            $datasetKey = (string) ($entry['dataset'] ?? '');
            $summaryParts = [];
            if ($categoryLabel !== '') {
                $summaryParts[] = ucfirst($categoryLabel);
            }
            if ($actionLabel !== '') {
                $summaryParts[] = ucfirst($actionLabel);
            }
            if ($datasetLabel !== '') {
                $summaryParts[] = $datasetLabel;
            }
            $summaryText = implode(' · ', $summaryParts);
            if ($summaryText === '') {
                $summaryText = 'Activity entry';
            }

            $metaParts = [];
            $metaParts[] = '#' . (int) ($entry['id'] ?? 0);
            if (!empty($entry['created_at_display'])) {
                $metaParts[] = (string) $entry['created_at_display'];
            }
            if (!empty($entry['performed_by_display'])) {
                $metaParts[] = (string) $entry['performed_by_display'];
            }

            $body .= '<details class="activity-entry">';
            $body .= '<summary><span class="activity-summary-main">' . htmlspecialchars($summaryText) . '</span><span class="activity-summary-meta">' . htmlspecialchars(implode(' · ', $metaParts)) . '</span></summary>';
            $body .= '<div class="activity-entry-body">';
            $body .= '<dl class="activity-entry-grid">';
            $body .= '<dt>Event ID</dt><dd>#' . htmlspecialchars((string) ($entry['id'] ?? 0)) . '</dd>';
            if (!empty($entry['created_at_display'])) {
                $body .= '<dt>Recorded at</dt><dd>' . htmlspecialchars((string) $entry['created_at_display']) . ' UTC</dd>';
            }
            if (!empty($entry['trigger'])) {
                $body .= '<dt>Trigger</dt><dd>' . htmlspecialchars((string) $entry['trigger']) . '</dd>';
            }
            if (!empty($entry['performed_by_display'])) {
                $body .= '<dt>Actor</dt><dd>' . htmlspecialchars((string) $entry['performed_by_display']) . '</dd>';
            }
            if ($datasetLabel !== '' || $datasetKey !== '') {
                $body .= '<dt>Dataset</dt><dd>' . htmlspecialchars($datasetLabel === '' ? $datasetKey : $datasetLabel);
                if ($datasetLabel !== '' && $datasetKey !== '' && $datasetKey !== $datasetLabel) {
                    $body .= ' <span class="activity-dataset-key">(' . htmlspecialchars($datasetKey) . ')</span>';
                }
                $body .= '</dd>';
            }
            if (!empty($entry['ip_address'])) {
                $body .= '<dt>IP address</dt><dd>' . htmlspecialchars((string) $entry['ip_address']) . '</dd>';
            }
            if (!empty($entry['user_agent_display'])) {
                $body .= '<dt>User agent</dt><dd>' . htmlspecialchars((string) $entry['user_agent_display']);
                if (!empty($entry['user_agent']) && (string) $entry['user_agent'] !== (string) $entry['user_agent_display']) {
                    $body .= ' <span class="activity-truncate-note">(truncated)</span>';
                }
                $body .= '</dd>';
            }
            $body .= '</dl>';

            if (!empty($entry['details_json'])) {
                $body .= '<div class="activity-json"><h4>Details</h4><pre>' . htmlspecialchars((string) $entry['details_json']) . '</pre></div>';
            }

            if (!empty($entry['context_json'])) {
                $body .= '<div class="activity-json"><h4>Context</h4><pre>' . htmlspecialchars((string) $entry['context_json']) . '</pre></div>';
            }

            $body .= '</div>';
            $body .= '</details>';
        }
        $body .= '</div>';
    }

    $body .= '</section>';

    if (!empty($themes)) {
        $body .= '<section class="theme-manager">';
        $body .= '<h2>Theme management</h2>';
        $body .= '<p>Author palette presets, tune CSS variables, and decide which theme greets new members.</p>';
        if ($themePolicy === 'disabled') {
            $body .= '<p class="notice warning">Member personalisation is currently disabled via the Theme Personalisation Policy setting.</p>';
        }
        $body .= '<div class="theme-grid">';
        foreach ($themes as $themeKey => $themeDefinition) {
            $label = $themeDefinition['label'] ?? $themeKey;
            $description = $themeDefinition['description'] ?? '';
            $tokensForTheme = $themeDefinition['tokens'] ?? [];
            $encodedTokens = htmlspecialchars(json_encode($tokensForTheme, JSON_UNESCAPED_SLASHES), ENT_QUOTES);
            $body .= '<article class="theme-card" data-theme-key="' . htmlspecialchars($themeKey) . '">';
            $body .= '<header><h3>' . htmlspecialchars($label) . '</h3>';
            if ($themeKey === $defaultTheme) {
                $body .= '<span class="theme-badge">Default</span>';
            }
            $body .= '</header>';
            if ($description !== '') {
                $body .= '<p class="theme-description">' . htmlspecialchars($description) . '</p>';
            }
            $body .= '<form method="post" action="/setup.php" class="theme-form" data-theme-preview data-theme-values="' . $encodedTokens . '">';
            $body .= '<input type="hidden" name="action" value="update_theme">';
            $body .= '<input type="hidden" name="theme_key" value="' . htmlspecialchars($themeKey) . '">';
            $body .= '<label class="field"><span class="field-label">Display label</span><span class="field-control"><input type="text" name="label" value="' . htmlspecialchars($label) . '"></span><span class="field-description">Shown to administrators and members when choosing a preset.</span></label>';
            $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="2">' . htmlspecialchars($description) . '</textarea></span></label>';
            $body .= '<div class="theme-token-grid">';
            foreach ($themeTokens as $tokenKey => $definition) {
                $tokenLabel = $definition['label'] ?? ucfirst($tokenKey);
                $tokenDescription = $definition['description'] ?? '';
                $cssVariable = $definition['css_variable'] ?? ('--fg-' . str_replace('_', '-', $tokenKey));
                $type = $definition['type'] ?? 'text';
                $value = $tokensForTheme[$tokenKey] ?? ($definition['default'] ?? '');
                $body .= '<label class="field" data-theme-token="' . htmlspecialchars($tokenKey) . '">';
                $body .= '<span class="field-label">' . htmlspecialchars($tokenLabel) . '</span>';
                if ($type === 'color') {
                    $body .= '<span class="field-control"><input type="color" name="tokens[' . htmlspecialchars($tokenKey) . ']" value="' . htmlspecialchars($value) . '" data-theme-token-input data-css-variable="' . htmlspecialchars($cssVariable) . '"></span>';
                } else {
                    $body .= '<span class="field-control"><input type="text" name="tokens[' . htmlspecialchars($tokenKey) . ']" value="' . htmlspecialchars($value) . '" data-theme-token-input data-css-variable="' . htmlspecialchars($cssVariable) . '"></span>';
                }
                $body .= '<span class="field-description">' . htmlspecialchars($tokenDescription) . ' · ' . htmlspecialchars($cssVariable) . '</span>';
                $body .= '</label>';
            }
            $body .= '</div>';
            $body .= '<div class="theme-preview" data-theme-preview-target>';
            $body .= '<div class="theme-preview-header">Preview</div>';
            $body .= '<p class="theme-preview-body">Interface text previews with the current palette.</p>';
            $body .= '<div class="accent">Accent example</div>';
            $body .= '<div class="swatch-row">';
            $body .= '<span class="swatch positive">Positive</span>';
            $body .= '<span class="swatch warning">Warning</span>';
            $body .= '<span class="swatch negative">Critical</span>';
            $body .= '</div>';
            $body .= '</div>';
            $body .= '<div class="action-row">';
            $body .= '<button type="submit" class="button primary">Save theme</button>';
            $body .= '<button type="button" class="button" data-theme-reset>Reapply stored values</button>';
            $body .= '</div>';
            $body .= '</form>';
            $body .= '<div class="theme-card-actions">';
            if ($themeKey !== $defaultTheme) {
                $body .= '<form method="post" action="/setup.php" class="inline-form">';
                $body .= '<input type="hidden" name="action" value="set_default_theme">';
                $body .= '<input type="hidden" name="theme_key" value="' . htmlspecialchars($themeKey) . '">';
                $body .= '<button type="submit" class="button">Set as default</button>';
                $body .= '</form>';
            } else {
                $body .= '<p class="theme-current">This theme is the default experience.</p>';
            }
            if (count($themes) > 1 && $themeKey !== $defaultTheme) {
                $body .= '<form method="post" action="/setup.php" class="inline-form">';
                $body .= '<input type="hidden" name="action" value="delete_theme">';
                $body .= '<input type="hidden" name="theme_key" value="' . htmlspecialchars($themeKey) . '">';
                $body .= '<button type="submit" class="button danger">Delete theme</button>';
                $body .= '</form>';
            }
            $body .= '</div>';
            $body .= '</article>';
        }
        $body .= '</div>';

        $defaultTokenValues = [];
        foreach ($themeTokens as $tokenKey => $definition) {
            $defaultTokenValues[$tokenKey] = $definition['default'] ?? '';
        }
        $encodedDefaults = htmlspecialchars(json_encode($defaultTokenValues, JSON_UNESCAPED_SLASHES), ENT_QUOTES);

        $body .= '<details class="theme-create" open>';
        $body .= '<summary>Create new theme</summary>';
        $body .= '<form method="post" action="/setup.php" class="theme-form" data-theme-preview data-theme-values="' . $encodedDefaults . '">';
        $body .= '<input type="hidden" name="action" value="create_theme">';
        $body .= '<label class="field"><span class="field-label">Theme key</span><span class="field-control"><input type="text" name="theme_key" pattern="[a-z0-9_-]+" required></span><span class="field-description">Use lowercase letters, numbers, hyphens, or underscores.</span></label>';
        $body .= '<label class="field"><span class="field-label">Display label</span><span class="field-control"><input type="text" name="label" placeholder="Aurora"></span></label>';
        $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="2" placeholder="Describe when to use this palette."></textarea></span></label>';
        $body .= '<div class="theme-token-grid">';
        foreach ($themeTokens as $tokenKey => $definition) {
            $tokenLabel = $definition['label'] ?? ucfirst($tokenKey);
            $cssVariable = $definition['css_variable'] ?? ('--fg-' . str_replace('_', '-', $tokenKey));
            $type = $definition['type'] ?? 'text';
            $value = $definition['default'] ?? '';
            $body .= '<label class="field" data-theme-token="' . htmlspecialchars($tokenKey) . '">';
            $body .= '<span class="field-label">' . htmlspecialchars($tokenLabel) . '</span>';
            if ($type === 'color') {
                $body .= '<span class="field-control"><input type="color" name="tokens[' . htmlspecialchars($tokenKey) . ']" value="' . htmlspecialchars($value) . '" data-theme-token-input data-css-variable="' . htmlspecialchars($cssVariable) . '"></span>';
            } else {
                $body .= '<span class="field-control"><input type="text" name="tokens[' . htmlspecialchars($tokenKey) . ']" value="' . htmlspecialchars($value) . '" data-theme-token-input data-css-variable="' . htmlspecialchars($cssVariable) . '"></span>';
            }
            $body .= '<span class="field-description">CSS variable ' . htmlspecialchars($cssVariable) . '</span>';
            $body .= '</label>';
        }
        $body .= '</div>';
        $body .= '<div class="theme-preview" data-theme-preview-target>';
        $body .= '<div class="theme-preview-header">Preview</div>';
        $body .= '<p class="theme-preview-body">Adjust the tokens to craft a new experience.</p>';
        $body .= '<div class="accent">Accent example</div>';
        $body .= '<div class="swatch-row">';
        $body .= '<span class="swatch positive">Positive</span>';
        $body .= '<span class="swatch warning">Warning</span>';
        $body .= '<span class="swatch negative">Critical</span>';
        $body .= '</div>';
        $body .= '</div>';
        $body .= '<div class="action-row">';
        $body .= '<button type="submit" class="button primary">Create theme</button>';
        $body .= '<button type="button" class="button" data-theme-reset>Reset to defaults</button>';
        $body .= '</div>';
        $body .= '</form>';
        $body .= '</details>';

        $body .= '</section>';
    }

    if (!empty($translationLocales) || !empty($translationTokens)) {
        $body .= '<section class="translations-manager">';
        $localeCount = count($translationLocales);
        $tokenCount = count($translationTokens);
        $body .= '<h2>Locale management</h2>';
        $body .= '<p>Adjust interface strings per locale and control which language acts as the fallback. Delegated settings govern the default locale presented to new accounts.</p>';
        $body .= '<p class="notice muted">Fallback locale: <strong>' . htmlspecialchars((string) $fallbackLocale) . '</strong> · Default locale setting: <strong>' . htmlspecialchars((string) $defaultLocaleSetting) . '</strong> · Policy: <strong>' . htmlspecialchars((string) $localePolicy) . '</strong> · Tokens: <strong>' . htmlspecialchars((string) $tokenCount) . '</strong> · Locales: <strong>' . htmlspecialchars((string) $localeCount) . '</strong></p>';

        $body .= '<div class="translation-token-collection">';
        $body .= '<h3>Translation tokens</h3>';
        $body .= '<p class="translation-summary">Define the reusable strings referenced across the interface. Tokens let you grow Filegate with new modules without touching PHP by pairing each feature with its own phrase.</p>';
        $body .= '<details class="translation-create translation-token-create">';
        $body .= '<summary>Create translation token</summary>';
        $body .= '<form method="post" action="/setup.php" class="translation-form create">';
        $body .= '<input type="hidden" name="action" value="translation_create_token">';
        $body .= '<label class="field"><span class="field-label">Token key</span><span class="field-control"><input type="text" name="token_key" pattern="[a-z0-9._-]+" required></span><span class="field-description">Use dot-separated namespaces (for example, <code>composer.publish.cta</code>).</span></label>';
        $body .= '<label class="field"><span class="field-label">Display label</span><span class="field-control"><input type="text" name="token_label" placeholder="Composer · Publish CTA"></span><span class="field-description">Shown to administrators and translators.</span></label>';
        $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="token_description" rows="2" placeholder="Explain where this string appears."></textarea></span></label>';
        $body .= '<div class="action-row"><button type="submit" class="button primary">Create token</button></div>';
        $body .= '</form>';
        $body .= '</details>';

        if (!empty($translationTokens)) {
            foreach ($translationTokens as $tokenKey => $tokenMeta) {
                $label = $tokenMeta['label'] ?? $tokenKey;
                $description = $tokenMeta['description'] ?? '';
                $coverageTotal = max(1, $localeCount);
                $coverageDefined = 0;
                $missingLocales = [];
                foreach ($translationLocales as $localeKey => $definition) {
                    $value = $definition['strings'][$tokenKey] ?? '';
                    if ((string) $value !== '') {
                        $coverageDefined++;
                    } else {
                        $missingLocales[] = $definition['label'] ?? $localeKey;
                    }
                }
                $isSeededToken = isset($defaultTranslationTokens[$tokenKey]);
                $body .= '<article class="translation-token-card" data-token="' . htmlspecialchars((string) $tokenKey) . '">';
                $body .= '<header><h4>' . htmlspecialchars((string) $label) . '</h4>';
                $badges = [];
                $badges[] = $coverageDefined . ' / ' . $coverageTotal . ' locales';
                $badges[] = $isSeededToken ? 'Seeded' : 'Custom';
                if (!empty($badges)) {
                    $body .= '<span class="translation-badges">';
                    foreach ($badges as $badge) {
                        $body .= '<span class="translation-badge">' . htmlspecialchars((string) $badge) . '</span>';
                    }
                    $body .= '</span>';
                }
                $body .= '</header>';
                $body .= '<p class="translation-token-key"><code>' . htmlspecialchars((string) $tokenKey) . '</code></p>';
                if ($description !== '') {
                    $body .= '<p class="translation-token-description">' . htmlspecialchars((string) $description) . '</p>';
                }
                if (!empty($missingLocales)) {
                    $body .= '<p class="translation-token-missing">Missing strings: ' . htmlspecialchars(implode(', ', array_map('strval', $missingLocales))) . '</p>';
                } else {
                    $body .= '<p class="translation-token-covered">Defined for every locale.</p>';
                }
                $body .= '<form method="post" action="/setup.php" class="translation-token-form">';
                $body .= '<input type="hidden" name="action" value="translation_update_token">';
                $body .= '<input type="hidden" name="token_key" value="' . htmlspecialchars((string) $tokenKey) . '">';
                $body .= '<label class="field"><span class="field-label">Display label</span><span class="field-control"><input type="text" name="token_label" value="' . htmlspecialchars((string) $label) . '"></span></label>';
                $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="token_description" rows="2">' . htmlspecialchars((string) $description) . '</textarea></span><span class="field-description">Help translators understand how the token is used.</span></label>';
                $body .= '<label class="field"><span class="field-label">Fill value</span><span class="field-control"><textarea name="fill_value" rows="2" placeholder="Optional string to cascade to locales"></textarea></span><span class="field-description">Use with the fill mode below to auto-populate locale strings when saving.</span></label>';
                $body .= '<label class="field"><span class="field-label">Fill mode</span><span class="field-control"><select name="fill_mode">';
                $body .= '<option value="">Do not apply fill value</option>';
                $body .= '<option value="missing">Fill missing locale strings only</option>';
                $body .= '<option value="all">Replace every locale string</option>';
                $body .= '</select></span></label>';
                $body .= '<div class="action-row"><button type="submit" class="button primary">Save token</button></div>';
                $body .= '</form>';
                $body .= '<div class="translation-token-actions">';
                if ($isSeededToken) {
                    $body .= '<p class="translation-token-note">Seeded tokens remain available to guarantee baseline navigation. Override their strings per locale to customise Filegate.</p>';
                } else {
                    $body .= '<form method="post" action="/setup.php" class="inline-form">';
                    $body .= '<input type="hidden" name="action" value="translation_delete_token">';
                    $body .= '<input type="hidden" name="token_key" value="' . htmlspecialchars((string) $tokenKey) . '">';
                    $body .= '<button type="submit" class="button danger">Delete token</button>';
                    $body .= '</form>';
                }
                $body .= '</div>';
                $body .= '</article>';
            }
        } else {
            $body .= '<p class="notice muted">No translation tokens are registered yet. Create one to begin localising new interface areas.</p>';
        }
        $body .= '</div>';

        if (!empty($translationLocales)) {
            $body .= '<h3>Locales</h3>';
            $body .= '<details class="translation-create">';
            $body .= '<summary>Create locale</summary>';
            $body .= '<form method="post" action="/setup.php" class="translation-form create">';
            $body .= '<input type="hidden" name="action" value="translation_create_locale">';
            $body .= '<label class="field"><span class="field-label">Locale key</span><span class="field-control"><input type="text" name="locale_key" pattern="[a-z0-9_-]+" required></span><span class="field-description">Use lowercase letters, numbers, hyphens, or underscores.</span></label>';
            $body .= '<label class="field"><span class="field-label">Display label</span><span class="field-control"><input type="text" name="locale_label" placeholder="English (Canada)"></span></label>';
            $body .= '<label class="field"><span class="field-label">Copy strings from</span><span class="field-control"><select name="copy_from">';
            foreach ($translationLocales as $key => $definition) {
                $label = $definition['label'] ?? $key;
                $body .= '<option value="' . htmlspecialchars((string) $key) . '">' . htmlspecialchars((string) $label) . '</option>';
            }
            $body .= '</select></span></label>';
            $body .= '<div class="action-row"><button type="submit" class="button primary">Create locale</button></div>';
            $body .= '</form>';
            $body .= '</details>';

            foreach ($translationLocales as $localeKey => $definition) {
                $label = $definition['label'] ?? $localeKey;
                $strings = $definition['strings'] ?? [];
                $isFallback = ($localeKey === $fallbackLocale);
                $isDefault = ((string) $defaultLocaleSetting === (string) $localeKey);

                $body .= '<article class="translation-card" data-locale="' . htmlspecialchars((string) $localeKey) . '">';
                $body .= '<header><h3>' . htmlspecialchars((string) $label) . '</h3>';
                $badges = [];
                if ($isFallback) {
                    $badges[] = 'Fallback';
                }
                if ($isDefault) {
                    $badges[] = 'Default setting';
                }
                if (!empty($badges)) {
                    $body .= '<span class="translation-badges">';
                    foreach ($badges as $badge) {
                        $body .= '<span class="translation-badge">' . htmlspecialchars($badge) . '</span>';
                    }
                    $body .= '</span>';
                }
                $body .= '</header>';

                $body .= '<form method="post" action="/setup.php" class="translation-form">';
                $body .= '<input type="hidden" name="action" value="translation_save_locale">';
                $body .= '<input type="hidden" name="locale" value="' . htmlspecialchars((string) $localeKey) . '">';
                $body .= '<label class="field"><span class="field-label">Display label</span><span class="field-control"><input type="text" name="locale_label" value="' . htmlspecialchars((string) $label) . '"></span></label>';
                $body .= '<div class="translation-token-grid">';
                foreach ($translationTokens as $tokenKey => $tokenMeta) {
                    $tokenLabel = $tokenMeta['label'] ?? $tokenKey;
                    $tokenDescription = $tokenMeta['description'] ?? '';
                    $value = $strings[$tokenKey] ?? '';
                    $body .= '<label class="field" data-token="' . htmlspecialchars((string) $tokenKey) . '">';
                    $body .= '<span class="field-label">' . htmlspecialchars((string) $tokenLabel) . '</span>';
                    $body .= '<span class="field-control"><textarea name="strings[' . htmlspecialchars((string) $tokenKey) . ']" rows="2">' . htmlspecialchars((string) $value) . '</textarea></span>';
                    if ($tokenDescription !== '') {
                        $body .= '<span class="field-description">' . htmlspecialchars((string) $tokenDescription) . '</span>';
                    }
                    $body .= '</label>';
                }
                $body .= '</div>';
                $body .= '<div class="action-row"><button type="submit" class="button primary">Save translations</button></div>';
                $body .= '</form>';

                $body .= '<div class="translation-secondary-actions">';
                if (!$isFallback) {
                    $body .= '<form method="post" action="/setup.php" class="inline-form">';
                    $body .= '<input type="hidden" name="action" value="translation_set_fallback">';
                    $body .= '<input type="hidden" name="locale" value="' . htmlspecialchars((string) $localeKey) . '">';
                    $body .= '<button type="submit" class="button">Set as fallback</button>';
                    $body .= '</form>';
                }
                if (count($translationLocales) > 1) {
                    $disabled = $isFallback ? ' disabled' : '';
                    $body .= '<form method="post" action="/setup.php" class="inline-form">';
                    $body .= '<input type="hidden" name="action" value="translation_delete_locale">';
                    $body .= '<input type="hidden" name="locale" value="' . htmlspecialchars((string) $localeKey) . '">';
                    $body .= '<button type="submit" class="button danger"' . $disabled . '>Delete locale</button>';
                    $body .= '</form>';
                }
                $body .= '</div>';

                $body .= '</article>';
            }
        }

        $body .= '</section>';
    }

    fg_render_layout('Asset Setup', $body);
}
