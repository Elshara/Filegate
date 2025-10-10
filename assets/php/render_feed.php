<?php

require_once __DIR__ . '/load_posts.php';
require_once __DIR__ . '/find_user_by_id.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/render_post_body.php';
require_once __DIR__ . '/load_template_options.php';
require_once __DIR__ . '/load_editor_options.php';
require_once __DIR__ . '/load_notification_channels.php';
require_once __DIR__ . '/translate.php';
require_once __DIR__ . '/load_project_status.php';
require_once __DIR__ . '/load_changelog.php';
require_once __DIR__ . '/load_feature_requests.php';
require_once __DIR__ . '/load_bug_reports.php';
require_once __DIR__ . '/load_events.php';
require_once __DIR__ . '/load_automations.php';
require_once __DIR__ . '/default_automations_dataset.php';
require_once __DIR__ . '/filter_knowledge_articles.php';
require_once __DIR__ . '/list_knowledge_categories.php';
require_once __DIR__ . '/get_asset_parameter_value.php';
require_once __DIR__ . '/list_content_modules.php';

function fg_render_feed(array $viewer): string
{
    $posts = fg_load_posts();
    $records = $posts['records'] ?? [];
    usort($records, static function ($a, $b) {
        return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
    });

    $viewerRole = strtolower((string) ($viewer['role'] ?? ''));
    $isMember = !empty($viewer);
    $canModerate = in_array($viewerRole, ['admin', 'moderator'], true);
    $canViewPrivate = $canModerate;

    $noticeCode = isset($_GET['notice']) ? (string) $_GET['notice'] : '';
    $errorCode = isset($_GET['error']) ? (string) $_GET['error'] : '';
    $alerts = [];
    if ($noticeCode === 'feature-request-created') {
        $alerts[] = ['type' => 'success', 'message' => 'Feature request submitted successfully.'];
    } elseif ($noticeCode === 'feature-request-supported') {
        $alerts[] = ['type' => 'success', 'message' => 'Your support was recorded.'];
    } elseif ($noticeCode === 'feature-request-withdrawn') {
        $alerts[] = ['type' => 'success', 'message' => 'Support withdrawn from the feature request.'];
    } elseif ($noticeCode === 'feature-request-updated') {
        $alerts[] = ['type' => 'success', 'message' => 'Feature request updated.'];
    } elseif ($noticeCode === 'feature-request-deleted') {
        $alerts[] = ['type' => 'success', 'message' => 'Feature request removed.'];
    } elseif ($noticeCode === 'bug-report-created') {
        $alerts[] = ['type' => 'success', 'message' => 'Bug report submitted successfully.'];
    } elseif ($noticeCode === 'bug-report-watching') {
        $alerts[] = ['type' => 'success', 'message' => 'You are now watching updates on that bug.'];
    } elseif ($noticeCode === 'bug-report-unwatched') {
        $alerts[] = ['type' => 'info', 'message' => 'You will no longer receive notifications for that bug.'];
    }

    if ($errorCode === 'feature-request-disabled') {
        $alerts[] = ['type' => 'error', 'message' => 'Feature requests are currently disabled.'];
    } elseif ($errorCode === 'feature-request-unauthorised') {
        $alerts[] = ['type' => 'error', 'message' => 'You do not have permission to manage that feature request.'];
    } elseif ($errorCode === 'feature-request-invalid') {
        $alerts[] = ['type' => 'error', 'message' => 'The requested feature entry could not be found.'];
    } elseif ($errorCode === 'bug-report-unauthorised') {
        $alerts[] = ['type' => 'error', 'message' => 'You do not have permission to file a bug right now.'];
    } elseif ($errorCode === 'bug-report-invalid') {
        $alerts[] = ['type' => 'error', 'message' => 'The requested bug report could not be located.'];
    }

    $featureRequestPolicy = strtolower((string) fg_get_setting('feature_request_policy', 'members'));
    if ($featureRequestPolicy === 'enabled') {
        $featureRequestPolicy = 'members';
    }
    $featureRequestDefaultVisibility = strtolower((string) fg_get_setting('feature_request_default_visibility', 'members'));

    $featureRequestStatusOptions = fg_get_setting('feature_request_statuses', ['open', 'researching', 'planned', 'in_progress', 'completed', 'declined']);
    if (!is_array($featureRequestStatusOptions) || empty($featureRequestStatusOptions)) {
        $featureRequestStatusOptions = ['open'];
    }
    $featureRequestStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $featureRequestStatusOptions)));
    if (empty($featureRequestStatusOptions)) {
        $featureRequestStatusOptions = ['open'];
    }

    $featureRequestPriorityOptions = fg_get_setting('feature_request_priorities', ['low', 'medium', 'high', 'critical']);
    if (!is_array($featureRequestPriorityOptions) || empty($featureRequestPriorityOptions)) {
        $featureRequestPriorityOptions = ['medium'];
    }
    $featureRequestPriorityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $featureRequestPriorityOptions)));
    if (empty($featureRequestPriorityOptions)) {
        $featureRequestPriorityOptions = ['medium'];
    }

    $featureRequestsDataset = fg_load_feature_requests();
    $featureRequestRecords = $featureRequestsDataset['records'] ?? [];
    $featureRequestEntries = [];
    $featureRequestStatusLabels = [];
    $featureRequestStatusCounts = [];
    foreach ($featureRequestStatusOptions as $statusOption) {
        $featureRequestStatusLabels[$statusOption] = ucwords(str_replace('_', ' ', $statusOption));
        $featureRequestStatusCounts[$statusOption] = 0;
    }
    $featureRequestPriorityLabels = [];
    $featureRequestPriorityOrder = [];
    foreach ($featureRequestPriorityOptions as $priorityIndex => $priorityOption) {
        $featureRequestPriorityLabels[$priorityOption] = ucwords(str_replace('_', ' ', $priorityOption));
        $featureRequestPriorityOrder[$priorityOption] = $priorityIndex;
    }
    $featureRequestTotalVotes = 0;

    if (is_array($featureRequestRecords)) {
        foreach ($featureRequestRecords as $record) {
            if (!is_array($record)) {
                continue;
            }

            $visibility = strtolower((string) ($record['visibility'] ?? $featureRequestDefaultVisibility));
            if ($visibility === 'private' && !$canModerate) {
                continue;
            }
            if ($visibility === 'members' && !$isMember) {
                continue;
            }

            $status = strtolower((string) ($record['status'] ?? $featureRequestStatusOptions[0]));
            if (!isset($featureRequestStatusLabels[$status])) {
                $featureRequestStatusLabels[$status] = ucwords(str_replace('_', ' ', $status));
                $featureRequestStatusCounts[$status] = 0;
            }
            $featureRequestStatusCounts[$status] = ($featureRequestStatusCounts[$status] ?? 0) + 1;

            $priority = strtolower((string) ($record['priority'] ?? $featureRequestPriorityOptions[0]));
            if (!isset($featureRequestPriorityLabels[$priority])) {
                $featureRequestPriorityLabels[$priority] = ucwords(str_replace('_', ' ', $priority));
                $featureRequestPriorityOrder[$priority] = count($featureRequestPriorityOrder);
            }

            $supporters = $record['supporters'] ?? [];
            if (!is_array($supporters)) {
                $supporters = [];
            }
            $supporters = array_values(array_unique(array_filter(array_map('intval', $supporters), static function ($value) {
                return $value > 0;
            })));

            $voteCount = (int) ($record['vote_count'] ?? count($supporters));
            if ($voteCount < count($supporters)) {
                $voteCount = count($supporters);
            }
            $featureRequestTotalVotes += $voteCount;

            $record['supporters'] = $supporters;
            $record['vote_count'] = $voteCount;
            $record['viewer_has_supported'] = in_array((int) ($viewer['id'] ?? 0), $supporters, true);
            $record['priority'] = $priority;
            $record['priority_rank'] = $featureRequestPriorityOrder[$priority] ?? count($featureRequestPriorityOrder);
            $record['status'] = $status;
            $record['visibility'] = $visibility;
            if (empty($record['last_activity_at'])) {
                $record['last_activity_at'] = $record['updated_at'] ?? $record['created_at'] ?? date(DATE_ATOM);
            }

            $featureRequestEntries[] = $record;
        }
    }

    if (!empty($featureRequestEntries)) {
        usort($featureRequestEntries, static function (array $a, array $b) {
            $votesA = (int) ($a['vote_count'] ?? 0);
            $votesB = (int) ($b['vote_count'] ?? 0);
            if ($votesA !== $votesB) {
                return $votesB <=> $votesA;
            }

            $rankA = (int) ($a['priority_rank'] ?? PHP_INT_MAX);
            $rankB = (int) ($b['priority_rank'] ?? PHP_INT_MAX);
            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            $timeA = strtotime((string) ($a['last_activity_at'] ?? $a['updated_at'] ?? $a['created_at'] ?? 'now'));
            $timeB = strtotime((string) ($b['last_activity_at'] ?? $b['updated_at'] ?? $b['created_at'] ?? 'now'));
            return $timeB <=> $timeA;
        });
    }

    $canSubmitFeatureRequests = $featureRequestPolicy !== 'disabled'
        && (
            $featureRequestPolicy === 'members'
            || ($featureRequestPolicy === 'moderators' && $canModerate)
            || ($featureRequestPolicy === 'admins' && $viewerRole === 'admin')
        );

    $featureRequestSubmissionMessage = '';
    if (!$canSubmitFeatureRequests) {
        if ($featureRequestPolicy === 'disabled') {
            $featureRequestSubmissionMessage = 'Feature request submissions are currently disabled.';
        } elseif ($featureRequestPolicy === 'admins') {
            $featureRequestSubmissionMessage = 'Only administrators can submit new feature requests right now.';
        } elseif ($featureRequestPolicy === 'moderators') {
            $featureRequestSubmissionMessage = 'Only administrators and moderators can submit new feature requests right now.';
        }
    }

    $bugPolicy = strtolower((string) fg_get_setting('bug_report_policy', 'members'));
    if ($bugPolicy === 'enabled') {
        $bugPolicy = 'members';
    }
    if (!in_array($bugPolicy, ['disabled', 'members', 'moderators', 'admins'], true)) {
        $bugPolicy = 'members';
    }

    $bugDefaultVisibility = strtolower((string) fg_get_setting('bug_report_default_visibility', 'members'));
    if (!in_array($bugDefaultVisibility, ['public', 'members', 'private'], true)) {
        $bugDefaultVisibility = 'members';
    }

    $bugStatusOptions = fg_get_setting('bug_report_statuses', ['new', 'triaged', 'in_progress', 'resolved', 'wont_fix', 'duplicate']);
    if (!is_array($bugStatusOptions) || empty($bugStatusOptions)) {
        $bugStatusOptions = ['new'];
    }
    $bugStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $bugStatusOptions)));
    if (empty($bugStatusOptions)) {
        $bugStatusOptions = ['new'];
    }

    $bugSeverityOptions = fg_get_setting('bug_report_severities', ['low', 'medium', 'high', 'critical']);
    if (!is_array($bugSeverityOptions) || empty($bugSeverityOptions)) {
        $bugSeverityOptions = ['medium'];
    }
    $bugSeverityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $bugSeverityOptions)));
    if (empty($bugSeverityOptions)) {
        $bugSeverityOptions = ['medium'];
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

    $bugReportsDataset = fg_load_bug_reports();
    $bugRecords = $bugReportsDataset['records'] ?? [];
    $bugEntries = [];
    $bugTotalWatchers = 0;
    if (is_array($bugRecords)) {
        foreach ($bugRecords as $bug) {
            if (!is_array($bug)) {
                continue;
            }

            $visibility = strtolower((string) ($bug['visibility'] ?? $bugDefaultVisibility));
            if (!in_array($visibility, ['public', 'members', 'private'], true)) {
                $visibility = $bugDefaultVisibility;
            }
            if ($visibility === 'private' && !$canModerate) {
                continue;
            }
            if ($visibility === 'members' && !$isMember) {
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

            $watchers = $bug['watchers'] ?? [];
            if (!is_array($watchers)) {
                $watchers = [];
            }
            $watchers = array_values(array_unique(array_filter(array_map('intval', $watchers), static function ($value) {
                return $value > 0;
            })));
            $bugTotalWatchers += count($watchers);

            $tags = $bug['tags'] ?? [];
            if (!is_array($tags)) {
                $tags = [];
            }
            $tags = array_values(array_filter(array_map(static function ($tag) {
                return trim((string) $tag);
            }, $tags), static function ($value) {
                return $value !== '';
            }));

            $steps = $bug['steps_to_reproduce'] ?? [];
            if (!is_array($steps)) {
                $steps = [];
            }
            $steps = array_values(array_filter(array_map(static function ($step) {
                return trim((string) $step);
            }, $steps), static function ($value) {
                return $value !== '';
            }));

            $versions = $bug['affected_versions'] ?? [];
            if (!is_array($versions)) {
                $versions = [];
            }
            $versions = array_values(array_filter(array_map(static function ($version) {
                return trim((string) $version);
            }, $versions), static function ($value) {
                return $value !== '';
            }));

            $links = $bug['reference_links'] ?? [];
            if (!is_array($links)) {
                $links = [];
            }
            $links = array_values(array_filter(array_map(static function ($link) {
                return trim((string) $link);
            }, $links), static function ($value) {
                return $value !== '';
            }));

            $attachments = $bug['attachments'] ?? [];
            if (!is_array($attachments)) {
                $attachments = [];
            }
            $attachments = array_values(array_filter(array_map(static function ($attachment) {
                return trim((string) $attachment);
            }, $attachments), static function ($value) {
                return $value !== '';
            }));

            $lastActivity = trim((string) ($bug['last_activity_at'] ?? $bug['updated_at'] ?? $bug['created_at'] ?? ''));
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
                'tags' => $tags,
                'steps_to_reproduce' => $steps,
                'affected_versions' => $versions,
                'reference_links' => $links,
                'attachments' => $attachments,
                'viewer_is_watching' => in_array($userId, $watchers, true),
                'last_activity_label' => $lastActivityLabel,
            ]);
        }
    }

    $automationStatusOptions = fg_get_setting('automation_statuses', ['enabled', 'paused', 'disabled']);
    if (!is_array($automationStatusOptions) || empty($automationStatusOptions)) {
        $automationStatusOptions = ['enabled', 'paused', 'disabled'];
    }
    $automationStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $automationStatusOptions)));
    if (empty($automationStatusOptions)) {
        $automationStatusOptions = ['enabled'];
    }
    $automationStatusLabels = [];
    $automationStatusCounts = [];
    foreach ($automationStatusOptions as $statusValue) {
        $automationStatusLabels[$statusValue] = ucwords(str_replace('_', ' ', $statusValue));
        $automationStatusCounts[$statusValue] = 0;
    }

    $automationTriggerOptions = fg_get_setting('automation_triggers', ['user_registered', 'post_published', 'feature_request_submitted', 'bug_report_created']);
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

    $automationPriorityOptions = fg_get_setting('automation_priority_options', ['low', 'medium', 'high']);
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

    try {
        $automationDataset = fg_load_automations();
        if (!isset($automationDataset['records']) || !is_array($automationDataset['records'])) {
            $automationDataset = fg_default_automations_dataset();
        }
    } catch (Throwable $exception) {
        $automationDataset = fg_default_automations_dataset();
    }

    $automationRecords = $automationDataset['records'] ?? [];
    $automationEntries = [];
    $automationTotalRuns = 0;
    $automationActiveCount = 0;
    $automationStatusRank = [];
    foreach ($automationStatusOptions as $index => $statusValue) {
        $automationStatusRank[$statusValue] = $index;
    }

    if (is_array($automationRecords)) {
        foreach ($automationRecords as $automation) {
            if (!is_array($automation)) {
                continue;
            }

            $status = strtolower((string) ($automation['status'] ?? $automationStatusOptions[0]));
            if (!isset($automationStatusLabels[$status])) {
                $automationStatusLabels[$status] = ucwords(str_replace('_', ' ', $status));
            }
            $automationStatusCounts[$status] = ($automationStatusCounts[$status] ?? 0) + 1;
            if ($status === 'enabled') {
                $automationActiveCount++;
            }

            $trigger = strtolower((string) ($automation['trigger'] ?? ($automationTriggerOptions[0] ?? 'user_registered')));
            if (!isset($automationTriggerLabels[$trigger])) {
                $automationTriggerLabels[$trigger] = ucwords(str_replace('_', ' ', $trigger));
            }

            $priority = strtolower((string) ($automation['priority'] ?? ($automationPriorityOptions[0] ?? 'medium')));
            if (!isset($automationPriorityLabels[$priority])) {
                $automationPriorityLabels[$priority] = ucwords(str_replace('_', ' ', $priority));
            }

            $runCount = (int) ($automation['run_count'] ?? 0);
            if ($runCount < 0) {
                $runCount = 0;
            }
            $automationTotalRuns += $runCount;

            $lastRunAt = trim((string) ($automation['last_run_at'] ?? ''));
            $lastRunLabel = '';
            if ($lastRunAt !== '') {
                $lastRunTimestamp = strtotime($lastRunAt);
                if ($lastRunTimestamp !== false) {
                    $lastRunLabel = date('M j, Y H:i', $lastRunTimestamp);
                }
            }

            $automationEntries[] = [
                'id' => (int) ($automation['id'] ?? 0),
                'name' => trim((string) ($automation['name'] ?? 'Untitled automation')),
                'description' => trim((string) ($automation['description'] ?? '')),
                'status' => $status,
                'status_class' => $automationStatusClass($status),
                'trigger' => $trigger,
                'priority' => $priority,
                'run_count' => $runCount,
                'run_limit' => $automation['run_limit'] ?? null,
                'last_run_label' => $lastRunLabel,
                'updated_at' => $automation['updated_at'] ?? $automation['created_at'] ?? '',
            ];
        }
    }

    if (!empty($automationEntries)) {
        usort($automationEntries, static function (array $a, array $b) use ($automationStatusRank) {
            $rankA = $automationStatusRank[$a['status'] ?? ''] ?? PHP_INT_MAX;
            $rankB = $automationStatusRank[$b['status'] ?? ''] ?? PHP_INT_MAX;
            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            $timeA = strtotime((string) ($a['updated_at'] ?? 'now'));
            $timeB = strtotime((string) ($b['updated_at'] ?? 'now'));
            return $timeB <=> $timeA;
        });
    }

    $automationFeedTotalRecords = count($automationEntries);
    $automationFeedHiddenCount = 0;

    $automationFeedLimit = (int) fg_get_setting('automation_feed_display_limit', 3);
    if ($automationFeedLimit < 1) {
        $automationFeedLimit = 3;
    }
    if ($automationFeedLimit > 0 && count($automationEntries) > $automationFeedLimit) {
        $automationEntries = array_slice($automationEntries, 0, $automationFeedLimit);
        $automationFeedHiddenCount = $automationFeedTotalRecords - count($automationEntries);
    }

    if (!empty($bugEntries)) {
        usort($bugEntries, static function (array $a, array $b) {
            $timeA = strtotime((string) ($a['last_activity_at'] ?? $a['updated_at'] ?? $a['created_at'] ?? 'now'));
            $timeB = strtotime((string) ($b['last_activity_at'] ?? $b['updated_at'] ?? $b['created_at'] ?? 'now'));
            return $timeB <=> $timeA;
        });
    }

    $bugFeedLimit = (int) fg_get_setting('bug_report_feed_display_limit', 5);
    if ($bugFeedLimit > 0 && count($bugEntries) > $bugFeedLimit) {
        $bugEntries = array_slice($bugEntries, 0, $bugFeedLimit);
    }

    $canSubmitBugReports = $bugPolicy !== 'disabled'
        && (
            $bugPolicy === 'members'
            || ($bugPolicy === 'moderators' && $canModerate)
            || ($bugPolicy === 'admins' && $role === 'admin')
        );

    $bugSubmissionMessage = '';
    if (!$canSubmitBugReports) {
        if ($bugPolicy === 'disabled') {
            $bugSubmissionMessage = 'Bug report submissions are currently disabled.';
        } elseif ($bugPolicy === 'admins') {
            $bugSubmissionMessage = 'Only administrators can submit new bug reports right now.';
        } elseif ($bugPolicy === 'moderators') {
            $bugSubmissionMessage = 'Only administrators and moderators can submit new bug reports right now.';
        }
    }

    $eventStatusOptions = fg_get_setting('event_statuses', ['draft', 'scheduled', 'completed', 'cancelled']);
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

    $eventPolicySetting = strtolower((string) fg_get_setting('event_policy', 'moderators'));
    if ($eventPolicySetting === 'enabled') {
        $eventPolicySetting = 'members';
    }
    if (!in_array($eventPolicySetting, ['disabled', 'members', 'moderators', 'admins'], true)) {
        $eventPolicySetting = 'moderators';
    }

    $eventDefaultVisibility = strtolower((string) fg_get_setting('event_default_visibility', 'members'));
    if (!in_array($eventDefaultVisibility, ['public', 'members', 'private'], true)) {
        $eventDefaultVisibility = 'members';
    }

    $eventVisibilityLabels = [
        'public' => 'Public',
        'members' => 'Members',
        'private' => 'Administrators',
    ];

    $eventFeedLimit = (int) fg_get_setting('event_feed_display_limit', 4);
    if ($eventFeedLimit < 1) {
        $eventFeedLimit = 4;
    }

    $eventDataset = fg_load_events();
    $eventRecords = $eventDataset['records'] ?? [];
    if (!is_array($eventRecords)) {
        $eventRecords = [];
    }

    $eventUserCache = [];
    $resolveUserLabel = static function (int $userId) use (&$eventUserCache) {
        if (isset($eventUserCache[$userId])) {
            return $eventUserCache[$userId];
        }
        $user = fg_find_user_by_id($userId);
        if ($user) {
            $label = trim((string) ($user['username'] ?? 'User #' . $userId));
            if ($label === '') {
                $label = 'User #' . $userId;
            }
            $role = trim((string) ($user['role'] ?? ''));
            if ($role !== '') {
                $label .= ' · ' . ucfirst($role);
            }
        } else {
            $label = 'User #' . $userId;
        }
        $eventUserCache[$userId] = $label;
        return $label;
    };

    $eventUpcomingEntries = [];
    $eventPastEntries = [];
    $eventUpcomingCount = 0;
    $eventPastCount = 0;
    $eventTotalRsvps = 0;
    $eventTotalCapacity = 0;
    $eventNowTimestamp = time();

    foreach ($eventRecords as $eventRecord) {
        if (!is_array($eventRecord)) {
            continue;
        }

        $status = strtolower((string) ($eventRecord['status'] ?? $eventStatusOptions[0]));
        if (!isset($eventStatusLabels[$status])) {
            $status = $eventStatusOptions[0];
        }
        if ($status === 'draft' && !$canModerate) {
            continue;
        }

        $visibility = strtolower((string) ($eventRecord['visibility'] ?? $eventDefaultVisibility));
        if ($visibility === 'private' && !$canModerate) {
            continue;
        }
        if ($visibility === 'members' && !$isMember) {
            continue;
        }
        if (!isset($eventVisibilityLabels[$visibility])) {
            $visibility = $eventDefaultVisibility;
        }

        $startTimestamp = $eventRecord['start_at'] ?? '';
        $endTimestamp = $eventRecord['end_at'] ?? '';
        $startTimestamp = $startTimestamp !== '' ? strtotime((string) $startTimestamp) : false;
        $endTimestamp = $endTimestamp !== '' ? strtotime((string) $endTimestamp) : false;
        if ($startTimestamp === false) {
            $startTimestamp = $eventNowTimestamp;
        }
        if ($endTimestamp === false) {
            $endTimestamp = $startTimestamp + 3600;
        }

        $isPast = $endTimestamp < $eventNowTimestamp;
        if ($isPast) {
            $eventPastCount++;
        } else {
            $eventUpcomingCount++;
        }

        $eventStatusCounts[$status] = ($eventStatusCounts[$status] ?? 0) + 1;

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
            $hostLabels[] = $resolveUserLabel($hostId);
        }

        $eventEntry = [
            'id' => (int) ($eventRecord['id'] ?? 0),
            'title' => trim((string) ($eventRecord['title'] ?? 'Untitled event')),
            'summary' => trim((string) ($eventRecord['summary'] ?? '')),
            'description' => trim((string) ($eventRecord['description'] ?? '')),
            'status' => $status,
            'status_label' => $eventStatusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)),
            'visibility' => $visibility,
            'visibility_label' => $eventVisibilityLabels[$visibility] ?? ucfirst($visibility),
            'start_timestamp' => $startTimestamp,
            'end_timestamp' => $endTimestamp,
            'start_label' => date('M j, Y H:i', $startTimestamp),
            'end_label' => date('M j, Y H:i', $endTimestamp),
            'location' => trim((string) ($eventRecord['location'] ?? '')),
            'location_url' => trim((string) ($eventRecord['location_url'] ?? '')),
            'timezone' => trim((string) ($eventRecord['timezone'] ?? 'UTC')),
            'allow_rsvp' => $allowRsvp,
            'rsvp_count' => count($rsvps),
            'rsvp_limit' => $rsvpLimit,
            'hosts' => $hostLabels,
            'is_past' => $isPast,
        ];

        if ($isPast) {
            $eventPastEntries[] = $eventEntry;
        } else {
            $eventUpcomingEntries[] = $eventEntry;
        }
    }

    if (!empty($eventUpcomingEntries)) {
        usort($eventUpcomingEntries, static function (array $a, array $b) {
            return ($a['start_timestamp'] ?? 0) <=> ($b['start_timestamp'] ?? 0);
        });
    }
    if (!empty($eventPastEntries)) {
        usort($eventPastEntries, static function (array $a, array $b) {
            return ($b['end_timestamp'] ?? 0) <=> ($a['end_timestamp'] ?? 0);
        });
    }

    $eventEntries = array_slice($eventUpcomingEntries, 0, $eventFeedLimit);
    if (count($eventEntries) < $eventFeedLimit && !empty($eventPastEntries)) {
        $eventEntries = array_merge($eventEntries, array_slice($eventPastEntries, 0, $eventFeedLimit - count($eventEntries)));
    }

    $eventFeedTotalRecords = count($eventUpcomingEntries) + count($eventPastEntries);
    $eventFeedHiddenCount = max(0, $eventFeedTotalRecords - count($eventEntries));

    $canCreateEvents = $eventPolicySetting !== 'disabled'
        && (
            $eventPolicySetting === 'members'
            || ($eventPolicySetting === 'moderators' && $canModerate)
            || ($eventPolicySetting === 'admins' && $viewerRole === 'admin')
        );

    $eventSubmissionMessage = '';
    if (!$canCreateEvents) {
        if ($eventPolicySetting === 'disabled') {
            $eventSubmissionMessage = 'Event creation is currently disabled.';
        } elseif ($eventPolicySetting === 'admins') {
            $eventSubmissionMessage = 'Only administrators can schedule new events right now.';
        } elseif ($eventPolicySetting === 'moderators') {
            $eventSubmissionMessage = 'Only administrators and moderators can schedule new events right now.';
        }
    }

    $roadmapDataset = fg_load_project_status();
    $roadmapRecords = $roadmapDataset['records'] ?? [];
    $roadmapEntries = [];
    if (is_array($roadmapRecords)) {
        foreach ($roadmapRecords as $entry) {
            if (is_array($entry)) {
                $roadmapEntries[] = $entry;
            }
        }
    }

    $statusLabels = [
        'in_progress' => 'In progress',
        'planned' => 'Planned',
        'built' => 'Built',
        'on_hold' => 'On hold',
    ];
    $statusCounts = [];
    $statusRank = ['in_progress' => 0, 'planned' => 1, 'built' => 2, 'on_hold' => 3];
    foreach ($statusLabels as $statusKey => $label) {
        $statusCounts[$statusKey] = 0;
    }

    $progressTotal = 0;
    $progressCount = 0;
    foreach ($roadmapEntries as $entry) {
        $state = (string) ($entry['status'] ?? 'planned');
        if (!isset($statusCounts[$state])) {
            $statusCounts[$state] = 0;
            $statusLabels[$state] = ucwords(str_replace('_', ' ', $state));
            $statusRank[$state] = count($statusRank);
        }
        $statusCounts[$state]++;
        $progress = (int) ($entry['progress'] ?? 0);
        if ($progress < 0) {
            $progress = 0;
        }
        if ($progress > 100) {
            $progress = 100;
        }
        $progressTotal += $progress;
        $progressCount++;
    }

    if (!empty($eventEntries) || $canCreateEvents) {
        $html .= '<section class="panel event-feed-panel">';
        $html .= '<h2>Events</h2>';
        $html .= '<p>Discover upcoming Filegate sessions, workshops, and launch milestones managed entirely from the shared host deployment.</p>';

        if (!empty($eventStatusCounts)) {
            $html .= '<div class="event-feed-summary">';
            foreach ($eventStatusCounts as $statusKey => $count) {
                $label = $eventStatusLabels[$statusKey] ?? ucwords(str_replace('_', ' ', $statusKey));
                $html .= '<article class="event-summary-chip event-status-' . htmlspecialchars($statusKey) . '">';
                $html .= '<h3>' . htmlspecialchars($label) . '</h3>';
                $html .= '<p class="event-summary-total">' . $count . ' ' . ($count === 1 ? 'event' : 'events') . '</p>';
                $html .= '</article>';
            }
            $html .= '<article class="event-summary-chip event-summary-upcoming">';
            $html .= '<h3>Upcoming</h3>';
            $html .= '<p class="event-summary-total">' . $eventUpcomingCount . '</p>';
            $html .= '</article>';
            $html .= '<article class="event-summary-chip event-summary-past">';
            $html .= '<h3>Past</h3>';
            $html .= '<p class="event-summary-total">' . $eventPastCount . '</p>';
            $html .= '</article>';
            if ($eventTotalRsvps > 0 || $eventTotalCapacity > 0) {
                $html .= '<article class="event-summary-chip event-summary-rsvp">';
                $html .= '<h3>RSVPs</h3>';
                $capacityLabel = $eventTotalCapacity > 0 ? ' / ' . $eventTotalCapacity : '';
                $html .= '<p class="event-summary-total">' . $eventTotalRsvps . $capacityLabel . '</p>';
                $html .= '</article>';
            }
            $html .= '</div>';
        }

        if (!empty($eventEntries)) {
            $html .= '<ul class="event-feed-list">';
            foreach ($eventEntries as $eventEntry) {
                $eventId = (int) ($eventEntry['id'] ?? 0);
                $statusKey = strtolower((string) ($eventEntry['status'] ?? 'scheduled'));
                $statusLabel = $eventEntry['status_label'] ?? ucwords(str_replace('_', ' ', $statusKey));
                $visibilityLabel = $eventEntry['visibility_label'] ?? '';
                $title = $eventEntry['title'] ?? 'Untitled event';
                $summaryText = $eventEntry['summary'] ?? '';
                $descriptionText = $eventEntry['description'] ?? '';
                $location = $eventEntry['location'] ?? '';
                $locationUrl = $eventEntry['location_url'] ?? '';
                $timezone = $eventEntry['timezone'] ?? 'UTC';
                $startLabel = $eventEntry['start_label'] ?? '';
                $endLabel = $eventEntry['end_label'] ?? '';
                $rsvpCount = (int) ($eventEntry['rsvp_count'] ?? 0);
                $rsvpLimit = $eventEntry['rsvp_limit'];
                $allowRsvp = !empty($eventEntry['allow_rsvp']);
                $hosts = $eventEntry['hosts'] ?? [];
                $isPast = !empty($eventEntry['is_past']);

                $html .= '<li class="event-feed-card event-status-' . htmlspecialchars($statusKey) . '" id="event-' . $eventId . '">';
                $html .= '<header class="event-feed-header">';
                $html .= '<span class="event-feed-status">' . htmlspecialchars($statusLabel) . '</span>';
                if ($visibilityLabel !== '') {
                    $html .= '<span class="event-feed-visibility">' . htmlspecialchars($visibilityLabel) . '</span>';
                }
                if ($isPast) {
                    $html .= '<span class="event-feed-flag">Past</span>';
                }
                $html .= '</header>';

                $html .= '<h3>' . htmlspecialchars($title) . '</h3>';
                if ($summaryText !== '') {
                    $html .= '<p class="event-feed-summary-text">' . htmlspecialchars($summaryText) . '</p>';
                }

                $metaParts = [];
                if ($startLabel !== '') {
                    $metaParts[] = 'Starts ' . $startLabel;
                }
                if ($endLabel !== '') {
                    $metaParts[] = 'Ends ' . $endLabel;
                }
                if ($timezone !== '') {
                    $metaParts[] = 'Timezone ' . $timezone;
                }
                if ($location !== '') {
                    if ($locationUrl !== '') {
                        $metaParts[] = 'Location <a href="' . htmlspecialchars($locationUrl) . '">' . htmlspecialchars($location) . '</a>';
                    } else {
                        $metaParts[] = 'Location ' . htmlspecialchars($location);
                    }
                }
                if ($allowRsvp) {
                    $capacityLabel = ($rsvpLimit ?? 0) > 0 ? ' / ' . (int) $rsvpLimit : '';
                    $metaParts[] = 'RSVPs ' . $rsvpCount . $capacityLabel;
                }
                if (!empty($hosts)) {
                    $metaParts[] = 'Hosts ' . htmlspecialchars(implode(', ', $hosts));
                }

                if (!empty($metaParts)) {
                    $html .= '<p class="event-feed-meta">' . implode(' · ', $metaParts) . '</p>';
                }

                if ($descriptionText !== '') {
                    $html .= '<details class="event-feed-description"><summary>Event details</summary><p>' . nl2br(htmlspecialchars($descriptionText)) . '</p></details>';
                }

                $html .= '</li>';
            }
            $html .= '</ul>';

            if ($eventFeedHiddenCount > 0) {
                $html .= '<p class="notice muted">' . $eventFeedHiddenCount . ' additional ' . ($eventFeedHiddenCount === 1 ? 'event is' : 'events are') . ' scheduled beyond this preview. Visit setup for the complete schedule.</p>';
            }
        } else {
            $html .= '<p class="notice muted">No events are scheduled yet. Check back soon or request one from the team.</p>';
        }

        if ($canCreateEvents) {
            $html .= '<p class="event-feed-actions"><a class="button secondary" href="/setup.php#event-manager">Manage events</a></p>';
        } elseif ($eventSubmissionMessage !== '') {
            $html .= '<p class="notice muted">' . htmlspecialchars($eventSubmissionMessage) . '</p>';
        }

        $html .= '</section>';
    }

    if (!empty($bugEntries) || $canSubmitBugReports) {
        $html .= '<section class="panel bug-report-panel">';
        $html .= '<h2>Bug tracker</h2>';
        $html .= '<p class="bug-report-intro">Monitor and triage issues raised by the community directly from Filegate.</p>';

        if (!empty($bugEntries)) {
            $html .= '<div class="bug-report-summary">';
            foreach ($bugStatusLabels as $statusKey => $label) {
                $count = (int) ($bugStatusCounts[$statusKey] ?? 0);
                $html .= '<article class="bug-report-chip bug-status-' . htmlspecialchars($statusKey) . '">';
                $html .= '<h3>' . htmlspecialchars($label) . '</h3>';
                $html .= '<p class="bug-report-total">' . $count . ' ' . ($count === 1 ? 'bug' : 'bugs') . '</p>';
                $html .= '</article>';
            }
            $html .= '<article class="bug-report-chip bug-watchers">';
            $html .= '<h3>Total watchers</h3>';
            $html .= '<p class="bug-report-total">' . $bugTotalWatchers . '</p>';
            $html .= '</article>';
            $html .= '</div>';

            $html .= '<ul class="bug-report-list">';
            foreach ($bugEntries as $entry) {
                $id = (int) ($entry['id'] ?? 0);
                $title = trim((string) ($entry['title'] ?? 'Untitled bug'));
                $summaryText = trim((string) ($entry['summary'] ?? ''));
                $detailsText = trim((string) ($entry['details'] ?? ''));
                $statusKey = strtolower((string) ($entry['status'] ?? 'new'));
                $severityKey = strtolower((string) ($entry['severity'] ?? 'medium'));
                $statusLabel = $bugStatusLabels[$statusKey] ?? ucwords(str_replace('_', ' ', $statusKey));
                $severityLabel = $bugSeverityLabels[$severityKey] ?? ucwords(str_replace('_', ' ', $severityKey));
                $watcherCount = count($entry['watchers'] ?? []);
                $visibilityKey = strtolower((string) ($entry['visibility'] ?? $bugDefaultVisibility));
                $visibilityLabel = $visibilityKey === 'members' ? 'Members' : ucfirst($visibilityKey);
                $environment = trim((string) ($entry['environment'] ?? ''));
                $resolutionNotes = trim((string) ($entry['resolution_notes'] ?? ''));
                $lastActivityLabel = (string) ($entry['last_activity_label'] ?? '');
                $steps = $entry['steps_to_reproduce'] ?? [];
                $tags = $entry['tags'] ?? [];
                $links = $entry['reference_links'] ?? [];
                $attachments = $entry['attachments'] ?? [];
                $viewerWatching = !empty($entry['viewer_is_watching']);

                $html .= '<li class="bug-report-card bug-status-' . htmlspecialchars($statusKey) . '" id="bug-report-' . $id . '">';
                $html .= '<header class="bug-report-card-header">';
                $html .= '<span class="bug-report-status">' . htmlspecialchars($statusLabel) . '</span>';
                $html .= '<span class="bug-report-severity">Severity: ' . htmlspecialchars($severityLabel) . '</span>';
                $html .= '<span class="bug-report-watchers">Watchers: ' . $watcherCount . '</span>';
                if ($lastActivityLabel !== '') {
                    $html .= '<span class="bug-report-updated">Updated ' . htmlspecialchars($lastActivityLabel) . '</span>';
                }
                $html .= '</header>';

                $html .= '<h3>' . htmlspecialchars($title) . '</h3>';
                if ($summaryText !== '') {
                    $html .= '<p class="bug-report-summary-text">' . htmlspecialchars($summaryText) . '</p>';
                }
                if ($detailsText !== '') {
                    $html .= '<details class="bug-report-details"><summary>Details</summary><p>' . nl2br(htmlspecialchars($detailsText)) . '</p></details>';
                }

                $metaParts = [];
                $metaParts[] = 'Visibility: ' . $visibilityLabel;
                if ($environment !== '') {
                    $metaParts[] = 'Environment: ' . $environment;
                }
                if ($resolutionNotes !== '') {
                    $metaParts[] = 'Resolution notes: ' . $resolutionNotes;
                }
                if (!empty($metaParts)) {
                    $html .= '<p class="bug-report-meta">' . htmlspecialchars(implode(' · ', $metaParts)) . '</p>';
                }

                if (!empty($steps)) {
                    $html .= '<ol class="bug-report-steps">';
                    foreach ($steps as $step) {
                        $html .= '<li>' . htmlspecialchars((string) $step) . '</li>';
                    }
                    $html .= '</ol>';
                }

                if (!empty($tags)) {
                    $html .= '<ul class="bug-report-tags">';
                    foreach ($tags as $tag) {
                        $html .= '<li>' . htmlspecialchars((string) $tag) . '</li>';
                    }
                    $html .= '</ul>';
                }

                if (!empty($links)) {
                    $html .= '<ul class="bug-report-links">';
                    foreach ($links as $link) {
                        $url = trim((string) $link);
                        if ($url === '') {
                            continue;
                        }
                        $html .= '<li><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a></li>';
                    }
                    $html .= '</ul>';
                }

                if (!empty($attachments)) {
                    $html .= '<ul class="bug-report-attachments">';
                    foreach ($attachments as $attachment) {
                        $html .= '<li>' . htmlspecialchars((string) $attachment) . '</li>';
                    }
                    $html .= '</ul>';
                }

                $html .= '<div class="bug-report-actions">';
                $html .= '<form method="post" action="/bug-report.php" class="inline-form bug-report-watch-form">';
                $html .= '<input type="hidden" name="action" value="toggle_watch">';
                $html .= '<input type="hidden" name="bug_report_id" value="' . $id . '">';
                $watchLabel = $viewerWatching ? 'Unwatch' : 'Watch bug';
                $watchClass = $viewerWatching ? 'button secondary' : 'button primary';
                $html .= '<button type="submit" class="' . $watchClass . '">' . htmlspecialchars($watchLabel) . '</button>';
                $html .= '</form>';
                if ($canModerate) {
                    $html .= '<a class="bug-report-manage" href="/setup.php#bug-report-manager">Manage in setup</a>';
                }
                $html .= '</div>';

                $html .= '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p class="notice muted">No bug reports yet. Help the team by logging the first issue below.</p>';
        }

        if (!$canSubmitBugReports && $bugSubmissionMessage !== '') {
            $html .= '<p class="notice muted">' . htmlspecialchars($bugSubmissionMessage) . '</p>';
        }

        if ($canSubmitBugReports) {
            $html .= '<article class="bug-report-card bug-report-create">';
            $html .= '<h3>Report a bug</h3>';
            $html .= '<form method="post" action="/bug-report.php" class="bug-report-form">';
            $html .= '<input type="hidden" name="action" value="create_bug_report">';

            if ($canModerate) {
                $html .= '<label>Status<select name="status">';
                foreach ($bugStatusOptions as $statusOption) {
                    $html .= '<option value="' . htmlspecialchars($statusOption) . '">' . htmlspecialchars($bugStatusLabels[$statusOption] ?? ucwords(str_replace('_', ' ', $statusOption))) . '</option>';
                }
                $html .= '</select></label>';
            } else {
                $html .= '<input type="hidden" name="status" value="' . htmlspecialchars($bugStatusOptions[0]) . '">';
            }

            $html .= '<label>Severity<select name="severity">';
            foreach ($bugSeverityOptions as $severityOption) {
                $html .= '<option value="' . htmlspecialchars($severityOption) . '">' . htmlspecialchars($bugSeverityLabels[$severityOption] ?? ucwords(str_replace('_', ' ', $severityOption))) . '</option>';
            }
            $html .= '</select></label>';

            if ($canModerate) {
                $html .= '<label>Visibility<select name="visibility">';
                foreach (['public' => 'Public', 'members' => 'Members', 'private' => 'Private'] as $value => $label) {
                    $selected = $value === $bugDefaultVisibility ? ' selected' : '';
                    $html .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
                }
                $html .= '</select></label>';
            } else {
                $html .= '<input type="hidden" name="visibility" value="' . htmlspecialchars($bugDefaultVisibility) . '">';
            }

            $html .= '<label>Title<input type="text" name="title" required></label>';
            $html .= '<label>Summary<textarea name="summary" rows="2" placeholder="Brief description of the issue"></textarea></label>';
            $html .= '<label>Details<textarea name="details" rows="4" placeholder="What happened and what did you expect?"></textarea></label>';
            $html .= '<label>Environment<input type="text" name="environment" placeholder="Browser, device, or platform"></label>';
            $html .= '<label>Steps to reproduce<textarea name="steps_to_reproduce" rows="3" placeholder="One step per line"></textarea></label>';
            $html .= '<label>Affected versions<textarea name="affected_versions" rows="2" placeholder="One version per line"></textarea></label>';
            $html .= '<label>Attachments<textarea name="attachments" rows="2" placeholder="Optional upload identifiers or paths"></textarea></label>';

            $html .= '<button type="submit" class="button primary">Submit bug report</button>';
            $html .= '</form>';
            $html .= '</article>';
        }

        $html .= '</section>';
    }

    if (!empty($roadmapEntries)) {
        usort($roadmapEntries, static function (array $a, array $b) use ($statusRank) {
            $stateA = (string) ($a['status'] ?? 'planned');
            $stateB = (string) ($b['status'] ?? 'planned');
            $rankA = $statusRank[$stateA] ?? PHP_INT_MAX;
            $rankB = $statusRank[$stateB] ?? PHP_INT_MAX;
            if ($rankA === $rankB) {
                $timeA = strtotime((string) ($a['updated_at'] ?? $a['created_at'] ?? 'now'));
                $timeB = strtotime((string) ($b['updated_at'] ?? $b['created_at'] ?? 'now'));
                return $timeB <=> $timeA;
            }

            return $rankA <=> $rankB;
        });
    }

    $averageProgress = $progressCount > 0 ? (int) round($progressTotal / $progressCount) : 0;

    $changelogDataset = fg_load_changelog();
    $changelogRecords = $changelogDataset['records'] ?? [];
    $changelogEntries = [];
    $nowTs = time();

    if (is_array($changelogRecords)) {
        foreach ($changelogRecords as $record) {
            if (!is_array($record)) {
                continue;
            }
            $visibility = strtolower((string) ($record['visibility'] ?? 'public'));
            if ($visibility === 'private' && !$canViewPrivate) {
                continue;
            }
            if ($visibility === 'members' && !$isMember) {
                continue;
            }

            $publishedAtRaw = $record['published_at'] ?? null;
            $publishedTimestamp = null;
            if ($publishedAtRaw !== null && $publishedAtRaw !== '') {
                $parsed = strtotime((string) $publishedAtRaw);
                if ($parsed !== false) {
                    $publishedTimestamp = $parsed;
                }
            }

            $isDraft = $publishedTimestamp === null;
            if ($isDraft && !$canViewPrivate) {
                continue;
            }
            if (!$isDraft && $publishedTimestamp !== null && $publishedTimestamp > $nowTs && !$canViewPrivate) {
                continue;
            }

            if ($isDraft) {
                $createdTs = strtotime((string) ($record['created_at'] ?? 'now'));
                if ($createdTs === false) {
                    $createdTs = $nowTs;
                }
                $publishedTimestamp = $createdTs;
            }

            $record['published_timestamp'] = $publishedTimestamp ?? $nowTs;
            $record['is_draft'] = $isDraft;
            $changelogEntries[] = $record;
        }
    }

    if (!empty($changelogEntries)) {
        usort($changelogEntries, static function (array $a, array $b) {
            return ($b['published_timestamp'] ?? 0) <=> ($a['published_timestamp'] ?? 0);
        });
    }

    $templates = fg_load_template_options();
    $template_select = '';
    foreach ($templates as $template) {
        $value = $template['name'] ?? '';
        if ($value === '') {
            continue;
        }
        $label = $template['label'] ?? $value;
        $template_select .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
    }
    if ($template_select === '') {
        $template_select = '<option value="standard">Standard</option>';
    }

    $editor = fg_load_editor_options();
    $notification_templates = $editor['variables']['notification_templates'] ?? ['post_update'];
    $notification_options = '';
    foreach ($notification_templates as $template) {
        $notification_options .= '<option value="' . htmlspecialchars($template) . '">' . htmlspecialchars(ucwords(str_replace('_', ' ', $template))) . '</option>';
    }

    $channels = fg_load_notification_channels();
    $channel_fields = '';
    foreach ($channels as $key => $channel) {
        $channel_fields .= '<label><input type="checkbox" name="notification_channels[]" value="' . htmlspecialchars($key) . '" checked> ' . htmlspecialchars($channel['label'] ?? $key) . '</label>';
    }

    $max_uploads = (int) fg_get_setting('upload_limits', 5);

    $composerHeading = fg_translate('feed.composer.heading', ['user' => $viewer, 'default' => 'Share something']);
    $html = '<section class="panel"><h1>' . htmlspecialchars($composerHeading) . '</h1>';
    $html .= '<form method="post" action="/post.php" enctype="multipart/form-data" class="post-composer" data-preview-target="#composer-preview" data-dataset-target="#composer-elements">';
    $html .= '<label>Content<textarea name="content" required data-preview-source></textarea></label>';
    $html .= '<label>Summary<textarea name="summary" placeholder="Short overview for notifications and cards"></textarea></label>';
    if (fg_get_setting('post_custom_types', 'enabled') !== 'disabled') {
        $html .= '<label>Custom type<input type="text" name="custom_type" placeholder="article, gallery, event…"></label>';
    }
    $html .= '<label>Template<select name="template">' . $template_select . '</select></label>';
    $html .= '<label>Content format<select name="content_format"><option value="html" selected>HTML</option><option value="xhtml">XHTML</option><option value="markdown">Markdown (stored as HTML)</option></select></label>';
    $html .= '<label>Tags<input type="text" name="tags" placeholder="design, release, changelog"></label>';
    $html .= '<fieldset class="inline-fieldset"><legend>Display options</legend>';
    $html .= '<label><input type="checkbox" name="display_statistics" value="1" checked> Show statistics</label>';
    $html .= '<label><input type="checkbox" name="display_embeds" value="1" checked> Show embeds</label>';
    $html .= '</fieldset>';
    $html .= '<fieldset class="inline-fieldset"><legend>Notification channels</legend>' . $channel_fields . '</fieldset>';
    $html .= '<label>Notification template<select name="notification_template">' . $notification_options . '</select></label>';
    $html .= '<label>Collaborators (usernames, comma separated)<input type="text" name="collaborators" placeholder="alex, taylor"></label>';
    $html .= '<label>Attachments<input type="file" name="attachments[]" multiple data-upload-input data-max="' . $max_uploads . '"></label>';
    $html .= '<details class="composer-help" data-dataset-name="editor_options" id="composer-editor-options"><summary>Editor controls</summary><div class="composer-elements" data-dataset-output hidden></div><button type="button" class="dataset-viewer" data-dataset="editor_options" data-expose="true" data-output="#composer-editor-options [data-dataset-output]">Load editor reference</button></details>';
    $html .= '<details class="composer-help" data-dataset-name="html5_elements"><summary>HTML5 element support</summary><div class="composer-elements" id="composer-elements" data-dataset-output hidden></div><button type="button" class="dataset-viewer" data-dataset="html5_elements" data-expose="true" data-output="#composer-elements">Load supported elements</button></details>';
    $html .= '<fieldset class="inline-fieldset"><legend>Conversation & privacy</legend>';
    $html .= '<label>Conversation style<select name="conversation_style">';
    foreach (['standard' => 'Standard', 'threaded' => 'Threaded', 'broadcast' => 'Broadcast'] as $value => $label) {
        $html .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
    }
    $html .= '</select></label>';
    $html .= '<label>Privacy<select name="privacy">';
    foreach (['public' => 'Public', 'connections' => 'Connections', 'private' => 'Private'] as $value => $label) {
        $html .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
    }
    $html .= '</select></label>';
    $html .= '</fieldset>';
    $html .= '<button type="submit">Publish</button>';
    $html .= '</form></section>';
    $html .= '<section class="panel preview-panel" id="composer-preview" data-preview-output hidden><h2>Live preview</h2><div class="preview-body" data-preview-body><div class="preview-placeholder">Start writing to see your live preview, embeds, and statistics.</div></div><div class="preview-embeds" data-preview-embeds hidden></div><dl class="preview-statistics" data-preview-stats hidden></dl><ul class="preview-attachments" data-upload-list hidden></ul></section>';

    $post_modules = fg_list_content_modules('posts', [
        'viewer' => $viewer,
        'enforce_visibility' => true,
        'statuses' => ['active'],
    ]);
    if (!empty($post_modules)) {
        $html .= '<section class="panel content-modules-panel">';
        $html .= '<h2>Guided content modules</h2>';
        $html .= '<p class="content-modules-intro">Launch guided composers for curated post types, complete with categories, field prompts, and wizard stages.</p>';
        $html .= '<ul class="content-module-list">';
        foreach ($post_modules as $module) {
            if (!is_array($module)) {
                continue;
            }
            $label = $module['label'] ?? ($module['key'] ?? 'Module');
            $description = trim((string) ($module['description'] ?? ''));
            $format = trim((string) ($module['format'] ?? ''));
            $categories = $module['categories'] ?? [];
            if (!is_array($categories)) {
                $categories = [];
            }
            $wizard_steps = $module['wizard_steps'] ?? [];
            if (!is_array($wizard_steps)) {
                $wizard_steps = [];
            }
            $moduleGuides = $module['guides'] ?? [];
            if (!is_array($moduleGuides)) {
                $moduleGuides = [];
            }
            $microGuides = [];
            foreach (($moduleGuides['micro'] ?? []) as $guide) {
                if (is_array($guide)) {
                    $title = trim((string) ($guide['title'] ?? ''));
                    $prompt = trim((string) ($guide['prompt'] ?? ''));
                    $microGuides[] = [$title, $prompt];
                } elseif (is_string($guide) && trim($guide) !== '') {
                    $microGuides[] = [trim($guide), ''];
                }
            }
            $macroGuides = [];
            foreach (($moduleGuides['macro'] ?? []) as $guide) {
                if (is_array($guide)) {
                    $title = trim((string) ($guide['title'] ?? ''));
                    $prompt = trim((string) ($guide['prompt'] ?? ''));
                    $macroGuides[] = [$title, $prompt];
                } elseif (is_string($guide) && trim($guide) !== '') {
                    $macroGuides[] = [trim($guide), ''];
                }
            }
            $moduleRelationships = $module['relationships'] ?? [];
            if (!is_array($moduleRelationships)) {
                $moduleRelationships = [];
            }
            $visibility = strtolower((string) ($module['visibility'] ?? 'members'));
            $allowedRoles = $module['allowed_roles'] ?? [];
            if (!is_array($allowedRoles)) {
                $allowedRoles = [];
            }
            $allowedRoles = array_values(array_filter(array_map(static function ($role) {
                return strtolower(trim((string) $role));
            }, $allowedRoles), static function ($role) {
                return $role !== '';
            }));
            $audienceBits = [];
            if ($visibility === 'admins') {
                $audienceBits[] = 'Admins only';
            } elseif ($visibility === 'everyone') {
                $audienceBits[] = 'Everyone';
            } else {
                $audienceBits[] = 'Members';
            }
            if (!empty($allowedRoles)) {
                $audienceBits[] = 'Roles: ' . implode(', ', array_map(static function ($role) {
                    return ucwords(str_replace('_', ' ', $role));
                }, $allowedRoles));
            }
            $html .= '<li class="content-module-card">';
            $html .= '<header><h3>' . htmlspecialchars($label) . '</h3>';
            if ($format !== '') {
                $html .= '<p class="content-module-format">Format: ' . htmlspecialchars($format) . '</p>';
            }
            $html .= '</header>';
            if (!empty($audienceBits)) {
                $html .= '<p class="content-module-audience">' . htmlspecialchars(implode(' · ', $audienceBits)) . '</p>';
            }
            if ($description !== '') {
                $html .= '<p class="content-module-description">' . htmlspecialchars($description) . '</p>';
            }
            if (!empty($categories)) {
                $html .= '<ul class="content-module-categories">';
                foreach ($categories as $category) {
                    $html .= '<li>' . htmlspecialchars((string) $category) . '</li>';
                }
                $html .= '</ul>';
            }
            if (!empty($wizard_steps)) {
                $html .= '<ol class="content-module-steps">';
                foreach ($wizard_steps as $step) {
                    $html .= '<li>' . htmlspecialchars((string) $step) . '</li>';
                }
                $html .= '</ol>';
            }
            if (!empty($microGuides) || !empty($macroGuides)) {
                $html .= '<details class="content-module-guides"><summary>Guidance</summary>';
                if (!empty($microGuides)) {
                    $html .= '<h4>Micro</h4><ul>';
                    foreach ($microGuides as $guide) {
                        [$title, $prompt] = $guide;
                        if ($title === '' && $prompt === '') {
                            continue;
                        }
                        $html .= '<li>' . htmlspecialchars($title === '' ? $prompt : $title);
                        if ($title !== '' && $prompt !== '') {
                            $html .= '<span> — ' . htmlspecialchars($prompt) . '</span>';
                        }
                        $html .= '</li>';
                    }
                    $html .= '</ul>';
                }
                if (!empty($macroGuides)) {
                    $html .= '<h4>Macro</h4><ul>';
                    foreach ($macroGuides as $guide) {
                        [$title, $prompt] = $guide;
                        if ($title === '' && $prompt === '') {
                            continue;
                        }
                        $html .= '<li>' . htmlspecialchars($title === '' ? $prompt : $title);
                        if ($title !== '' && $prompt !== '') {
                            $html .= '<span> — ' . htmlspecialchars($prompt) . '</span>';
                        }
                        $html .= '</li>';
                    }
                    $html .= '</ul>';
                }
                $html .= '</details>';
            }
            if (!empty($moduleRelationships)) {
                $html .= '<details class="content-module-relationships"><summary>Connected modules</summary><ul>';
                foreach ($moduleRelationships as $relationship) {
                    if (!is_array($relationship)) {
                        continue;
                    }
                    $type = trim((string) ($relationship['type'] ?? 'related'));
                    if ($type === '') {
                        $type = 'related';
                    }
                    $targetKey = trim((string) ($relationship['module_key'] ?? $relationship['module_reference'] ?? ''));
                    if ($targetKey === '') {
                        continue;
                    }
                    $targetLabel = trim((string) ($relationship['module_label'] ?? ''));
                    if ($targetLabel === '' && isset($post_modules[$targetKey]) && is_array($post_modules[$targetKey])) {
                        $targetLabel = trim((string) ($post_modules[$targetKey]['label'] ?? ''));
                    }
                    $description = trim((string) ($relationship['description'] ?? ''));
                    $html .= '<li><strong>' . htmlspecialchars(ucfirst($type)) . '</strong>: ' . htmlspecialchars($targetLabel !== '' ? $targetLabel : $targetKey);
                    if ($targetLabel !== '' && strcasecmp($targetLabel, $targetKey) !== 0) {
                        $html .= ' <code>' . htmlspecialchars($targetKey) . '</code>';
                    }
                    if ($description !== '') {
                        $html .= '<span> — ' . htmlspecialchars($description) . '</span>';
                    }
                    $html .= '</li>';
                }
                $html .= '</ul></details>';
            }
            $html .= '<p class="content-module-actions"><a class="button" href="/post.php?module=' . htmlspecialchars($module['key']) . '">Launch guided composer</a></p>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</section>';
    }

    if (!empty($alerts)) {
        $html .= '<section class="panel feed-alerts">';
        foreach ($alerts as $alert) {
            $class = $alert['type'] === 'error' ? 'notice error' : 'notice success';
            $html .= '<div class="' . $class . '">' . htmlspecialchars($alert['message']) . '</div>';
        }
        $html .= '</section>';
    }

    if (!empty($featureRequestEntries) || $canSubmitFeatureRequests) {
        $html .= '<section class="panel feature-request-panel">';
        $html .= '<h2>Feature requests</h2>';
        $html .= '<p class="feature-request-intro">Capture, review, and prioritise ideas without relying on remote services.</p>';

        if (!empty($featureRequestEntries)) {
            $html .= '<div class="feature-request-summary">';
            foreach ($featureRequestStatusLabels as $statusKey => $label) {
                $count = (int) ($featureRequestStatusCounts[$statusKey] ?? 0);
                $html .= '<article class="feature-request-chip feature-request-status-' . htmlspecialchars($statusKey) . '">';
                $html .= '<h3>' . htmlspecialchars($label) . '</h3>';
                $html .= '<p class="feature-request-total">' . $count . ' ' . ($count === 1 ? 'idea' : 'ideas') . '</p>';
                $html .= '</article>';
            }
            $html .= '<article class="feature-request-chip feature-request-votes">';
            $html .= '<h3>Total support</h3>';
            $html .= '<p class="feature-request-total">' . $featureRequestTotalVotes . '</p>';
            $html .= '</article>';
            $html .= '</div>';

            $html .= '<ul class="feature-request-list">';
            foreach ($featureRequestEntries as $entry) {
                $id = (int) ($entry['id'] ?? 0);
                $title = trim((string) ($entry['title'] ?? 'Untitled request'));
                $summaryText = trim((string) ($entry['summary'] ?? ''));
                $detailsText = trim((string) ($entry['details'] ?? ''));
                $statusKey = strtolower((string) ($entry['status'] ?? 'open'));
                $statusLabel = $featureRequestStatusLabels[$statusKey] ?? ucwords(str_replace('_', ' ', $statusKey));
                $priorityKey = strtolower((string) ($entry['priority'] ?? ''));
                $priorityLabel = $featureRequestPriorityLabels[$priorityKey] ?? ucwords(str_replace('_', ' ', $priorityKey));
                $visibilityKey = strtolower((string) ($entry['visibility'] ?? $featureRequestDefaultVisibility));
                $visibilityLabel = $visibilityKey === 'members' ? 'Members' : ucfirst($visibilityKey);
                $impactScore = (int) ($entry['impact'] ?? 0);
                $effortScore = (int) ($entry['effort'] ?? 0);
                $voteCount = (int) ($entry['vote_count'] ?? 0);
                $lastActivity = (string) ($entry['last_activity_at'] ?? $entry['updated_at'] ?? $entry['created_at'] ?? '');
                $lastActivityLabel = '';
                if ($lastActivity !== '') {
                    $timestamp = strtotime($lastActivity);
                    if ($timestamp) {
                        $lastActivityLabel = 'Updated ' . date('M j, Y', $timestamp);
                    }
                }

                $ownerRole = (string) ($entry['owner_role'] ?? '');
                $ownerUserId = $entry['owner_user_id'] ?? null;
                $ownerLabel = '';
                if ($ownerUserId) {
                    $ownerUser = fg_find_user_by_id((int) $ownerUserId);
                    if ($ownerUser) {
                        $ownerLabel = '@' . ($ownerUser['username'] ?? $ownerUserId);
                    }
                }
                $requestorLabel = '';
                if (!empty($entry['requestor_user_id'])) {
                    $requestorUser = fg_find_user_by_id((int) $entry['requestor_user_id']);
                    if ($requestorUser) {
                        $requestorLabel = '@' . ($requestorUser['username'] ?? $entry['requestor_user_id']);
                    }
                }

                $tags = $entry['tags'] ?? [];
                if (!is_array($tags)) {
                    $tags = [];
                }
                $referenceLinks = $entry['reference_links'] ?? [];
                if (!is_array($referenceLinks)) {
                    $referenceLinks = [];
                }

                $html .= '<li class="feature-request-card feature-request-priority-' . htmlspecialchars($priorityKey) . '" id="feature-request-' . $id . '">';
                $html .= '<header class="feature-request-header">';
                $html .= '<span class="feature-request-status feature-request-status-' . htmlspecialchars($statusKey) . '">' . htmlspecialchars($statusLabel) . '</span>';
                $html .= '<span class="feature-request-priority-label">' . htmlspecialchars($priorityLabel) . '</span>';
                $html .= '<span class="feature-request-support-count">' . $voteCount . ' ' . ($voteCount === 1 ? 'supporter' : 'supporters') . '</span>';
                if ($lastActivityLabel !== '') {
                    $html .= '<span class="feature-request-updated">' . htmlspecialchars($lastActivityLabel) . '</span>';
                }
                $html .= '</header>';

                $html .= '<h3>' . htmlspecialchars($title) . '</h3>';
                if ($summaryText !== '') {
                    $html .= '<p class="feature-request-summary-text">' . htmlspecialchars($summaryText) . '</p>';
                }
                if ($detailsText !== '') {
                    $html .= '<details class="feature-request-details"><summary>Details</summary><p>' . nl2br(htmlspecialchars($detailsText)) . '</p></details>';
                }

                $metaParts = [];
                if ($impactScore > 0) {
                    $metaParts[] = 'Impact ' . $impactScore . '/5';
                }
                if ($effortScore > 0) {
                    $metaParts[] = 'Effort ' . $effortScore . '/5';
                }
                $metaParts[] = 'Visibility: ' . $visibilityLabel;
                if ($ownerRole !== '') {
                    $metaParts[] = 'Owner role: ' . ucfirst($ownerRole);
                }
                if ($ownerLabel !== '') {
                    $metaParts[] = 'Owner: ' . $ownerLabel;
                }
                if ($requestorLabel !== '') {
                    $metaParts[] = 'Requested by ' . $requestorLabel;
                }
                if (!empty($metaParts)) {
                    $html .= '<p class="feature-request-meta">' . htmlspecialchars(implode(' · ', $metaParts)) . '</p>';
                }

                if (!empty($tags)) {
                    $html .= '<ul class="feature-request-tags">';
                    foreach ($tags as $tag) {
                        $html .= '<li>' . htmlspecialchars((string) $tag) . '</li>';
                    }
                    $html .= '</ul>';
                }

                if (!empty($referenceLinks)) {
                    $html .= '<ul class="feature-request-links">';
                    foreach ($referenceLinks as $link) {
                        $url = trim((string) $link);
                        if ($url === '') {
                            continue;
                        }
                        $html .= '<li><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a></li>';
                    }
                    $html .= '</ul>';
                }

                $adminNotes = trim((string) ($entry['admin_notes'] ?? ''));
                if ($canModerate && $adminNotes !== '') {
                    $html .= '<p class="feature-request-notes"><strong>Notes:</strong> ' . htmlspecialchars($adminNotes) . '</p>';
                }

                if ($canModerate && !empty($entry['supporters'])) {
                    $supporterLabels = [];
                    foreach ($entry['supporters'] as $supporterId) {
                        $supporterUser = fg_find_user_by_id((int) $supporterId);
                        if ($supporterUser) {
                            $supporterLabels[] = '@' . ($supporterUser['username'] ?? $supporterId);
                        } else {
                            $supporterLabels[] = '#' . $supporterId;
                        }
                    }
                    if (!empty($supporterLabels)) {
                        $html .= '<p class="feature-request-supporters"><strong>Supporters:</strong> ' . htmlspecialchars(implode(', ', $supporterLabels)) . '</p>';
                    }
                }

                $html .= '<div class="feature-request-actions">';
                $html .= '<form method="post" action="/feature-request.php" class="inline-form feature-request-support-form">';
                $html .= '<input type="hidden" name="action" value="toggle_support">';
                $html .= '<input type="hidden" name="feature_request_id" value="' . $id . '">';
                $buttonClass = !empty($entry['viewer_has_supported']) ? 'button secondary' : 'button primary';
                $buttonLabel = !empty($entry['viewer_has_supported']) ? 'Withdraw support' : 'Support idea';
                $html .= '<button type="submit" class="' . $buttonClass . '">' . htmlspecialchars($buttonLabel) . '</button>';
                $html .= '</form>';
                if ($canModerate) {
                    $html .= '<a class="feature-request-manage" href="/setup.php#feature-requests">Manage in setup</a>';
                }
                $html .= '</div>';

                $html .= '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p class="notice muted">No feature requests yet. Be the first to propose an idea below.</p>';
        }

        if (!$canSubmitFeatureRequests && $featureRequestSubmissionMessage !== '') {
            $html .= '<p class="notice muted">' . htmlspecialchars($featureRequestSubmissionMessage) . '</p>';
        }

        if ($canSubmitFeatureRequests) {
            $html .= '<article class="feature-request-card feature-request-create">';
            $html .= '<h3>Submit a new idea</h3>';
            $html .= '<form method="post" action="/feature-request.php" class="feature-request-form">';
            $html .= '<input type="hidden" name="action" value="create_feature_request">';

            if ($canModerate) {
                $html .= '<label>Workflow status<select name="status">';
                foreach ($featureRequestStatusOptions as $statusOption) {
                    $html .= '<option value="' . htmlspecialchars($statusOption) . '">' . htmlspecialchars($featureRequestStatusLabels[$statusOption] ?? ucwords(str_replace('_', ' ', $statusOption))) . '</option>';
                }
                $html .= '</select></label>';
            } else {
                $defaultStatus = $featureRequestStatusOptions[0];
                $html .= '<input type="hidden" name="status" value="' . htmlspecialchars($defaultStatus) . '">';
            }

            $html .= '<label>Title<input type="text" name="title" required></label>';
            $html .= '<label>Summary<textarea name="summary" rows="2" placeholder="Short elevator pitch"></textarea></label>';
            $html .= '<label>Details<textarea name="details" rows="4" placeholder="Explain the problem, audience, and success criteria"></textarea></label>';

            $html .= '<label>Priority<select name="priority">';
            foreach ($featureRequestPriorityOptions as $priorityOption) {
                $html .= '<option value="' . htmlspecialchars($priorityOption) . '">' . htmlspecialchars($featureRequestPriorityLabels[$priorityOption] ?? ucwords(str_replace('_', ' ', $priorityOption))) . '</option>';
            }
            $html .= '</select></label>';

            $html .= '<div class="feature-request-score-grid">';
            $html .= '<label>Impact (1-5)<input type="number" name="impact" min="1" max="5" value="3"></label>';
            $html .= '<label>Effort (1-5)<input type="number" name="effort" min="1" max="5" value="3"></label>';
            $html .= '</div>';

            if ($canModerate) {
                $html .= '<label>Visibility<select name="visibility">';
                foreach (['public' => 'Public', 'members' => 'Members', 'private' => 'Private'] as $value => $label) {
                    $selected = $value === $featureRequestDefaultVisibility ? ' selected' : '';
                    $html .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
                }
                $html .= '</select></label>';
            } else {
                $html .= '<input type="hidden" name="visibility" value="' . htmlspecialchars($featureRequestDefaultVisibility) . '">';
            }

            $html .= '<label>Tags<input type="text" name="tags" placeholder="design, accessibility, onboarding"></label>';
            $html .= '<label>Reference links<textarea name="reference_links" rows="2" placeholder="One URL or internal path per line"></textarea></label>';

            $html .= '<button type="submit" class="button primary">Submit feature request</button>';
            $html .= '</form>';
            $html .= '</article>';
        }

        $html .= '</section>';
    }

    if (!empty($roadmapEntries)) {
        $html .= '<section class="panel roadmap-feed-panel">';
        $html .= '<h2>Roadmap</h2>';
        $html .= '<p class="roadmap-panel-intro">A quick look at what is built, underway, and planned across Filegate.</p>';
        $html .= '<div class="roadmap-summary">';
        foreach ($statusLabels as $statusKey => $label) {
            $count = (int) ($statusCounts[$statusKey] ?? 0);
            $html .= '<article class="roadmap-chip roadmap-status-' . htmlspecialchars($statusKey) . '">';
            $html .= '<h3>' . htmlspecialchars($label) . '</h3>';
            $html .= '<p class="roadmap-total">' . $count . ' ' . ($count === 1 ? 'item' : 'items') . '</p>';
            $html .= '</article>';
        }
        $html .= '<article class="roadmap-chip roadmap-progress">';
        $html .= '<h3>Average progress</h3>';
        $html .= '<p class="roadmap-total">' . $averageProgress . '%</p>';
        $html .= '</article>';
        $html .= '</div>';

        $html .= '<ul class="roadmap-feed-list">';
        $maxEntries = min(count($roadmapEntries), 5);
        for ($i = 0; $i < $maxEntries; $i++) {
            $entry = $roadmapEntries[$i];
            $title = trim((string) ($entry['title'] ?? 'Untitled milestone'));
            $summary = trim((string) ($entry['summary'] ?? ''));
            $state = (string) ($entry['status'] ?? 'planned');
            $statusLabel = $statusLabels[$state] ?? ucwords(str_replace('_', ' ', $state));
            $progress = (int) ($entry['progress'] ?? 0);
            if ($progress < 0) {
                $progress = 0;
            }
            if ($progress > 100) {
                $progress = 100;
            }
            $category = trim((string) ($entry['category'] ?? ''));
            $milestone = trim((string) ($entry['milestone'] ?? ''));
            $updatedAt = (string) ($entry['updated_at'] ?? $entry['created_at'] ?? '');
            $updatedLabel = '';
            if ($updatedAt !== '') {
                $timestamp = strtotime($updatedAt);
                if ($timestamp) {
                    $updatedLabel = 'Updated ' . date('M j, Y', $timestamp);
                }
            }

            $html .= '<li>';
            $html .= '<div class="roadmap-feed-title"><strong>' . htmlspecialchars($title) . '</strong><span class="roadmap-feed-status roadmap-feed-status-' . htmlspecialchars($state) . '">' . htmlspecialchars($statusLabel) . '</span></div>';
            if ($summary !== '') {
                $html .= '<p>' . htmlspecialchars($summary) . '</p>';
            }
            $metaParts = [];
            $metaParts[] = 'Progress ' . $progress . '%';
            if ($category !== '') {
                $metaParts[] = 'Category: ' . $category;
            }
            if ($milestone !== '') {
                $metaParts[] = 'Milestone: ' . $milestone;
            }
            if ($updatedLabel !== '') {
                $metaParts[] = $updatedLabel;
            }
            $html .= '<p class="roadmap-feed-meta">' . htmlspecialchars(implode(' · ', $metaParts)) . '</p>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</section>';
    }

    if (!empty($changelogEntries)) {
        $html .= '<section class="panel changelog-feed-panel">';
        $html .= '<h2>Changelog</h2>';
        $html .= '<p class="changelog-panel-intro">Recent releases and improvements published across profiles and datasets.</p>';
        $html .= '<ul class="changelog-feed-list">';
        $maxChangelog = min(count($changelogEntries), 6);
        for ($i = 0; $i < $maxChangelog; $i++) {
            $entry = $changelogEntries[$i];
            $title = trim((string) ($entry['title'] ?? 'Untitled update'));
            $summary = trim((string) ($entry['summary'] ?? ''));
            $type = strtolower((string) ($entry['type'] ?? 'announcement'));
            $typeLabel = ucwords(str_replace('_', ' ', $type));
            $tags = $entry['tags'] ?? [];
            if (!is_array($tags)) {
                $tags = [];
            }
            $highlight = !empty($entry['highlight']);
            $publishedTimestamp = $entry['published_timestamp'] ?? null;
            $isDraft = !empty($entry['is_draft']);
            $publishedLabel = '';
            if ($isDraft) {
                $publishedLabel = 'Draft';
            } elseif ($publishedTimestamp) {
                if ($publishedTimestamp > $nowTs) {
                    $publishedLabel = 'Scheduled ' . date('M j, Y', (int) $publishedTimestamp);
                } else {
                    $publishedLabel = date('M j, Y', (int) $publishedTimestamp);
                }
            }

            $html .= '<li class="changelog-feed-item' . ($highlight ? ' changelog-feed-highlight' : '') . '">';
            $html .= '<div class="changelog-feed-header">';
            $html .= '<span class="changelog-feed-type">' . htmlspecialchars($typeLabel) . '</span>';
            if ($publishedLabel !== '') {
                $html .= '<span class="changelog-feed-date">' . htmlspecialchars($publishedLabel) . '</span>';
            }
            $html .= '</div>';
            $html .= '<h3>' . htmlspecialchars($title) . '</h3>';
            if ($summary !== '') {
                $html .= '<p>' . htmlspecialchars($summary) . '</p>';
            }
            if (!empty($tags)) {
                $html .= '<ul class="changelog-feed-tags">';
                foreach ($tags as $tag) {
                    $html .= '<li>' . htmlspecialchars((string) $tag) . '</li>';
                }
                $html .= '</ul>';
            }
            if (!empty($entry['links']) && is_array($entry['links'])) {
                $html .= '<ul class="changelog-feed-links">';
                foreach ($entry['links'] as $link) {
                    $url = (string) $link;
                    if ($url === '') {
                        continue;
                    }
                    $html .= '<li><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a></li>';
                }
                $html .= '</ul>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</section>';
    }

    if (!empty($automationEntries)) {
        $html .= '<section class="panel automation-feed-panel">';
        $html .= '<h2>Automation rules</h2>';
        $html .= '<p class="automation-panel-intro">Local-first workflows currently configured on this Filegate instance.</p>';

        $html .= '<div class="automation-feed-summary">';
        foreach ($automationStatusLabels as $statusKey => $label) {
            $count = (int) ($automationStatusCounts[$statusKey] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $statusClass = $automationStatusClass((string) $statusKey);
            $html .= '<article class="automation-feed-chip automation-status-' . htmlspecialchars($statusClass) . '">';
            $html .= '<h3>' . htmlspecialchars($label) . '</h3>';
            $html .= '<p class="automation-feed-total">' . $count . ' ' . ($count === 1 ? 'rule' : 'rules') . '</p>';
            $html .= '</article>';
        }
        $html .= '<article class="automation-feed-chip automation-feed-active">';
        $html .= '<h3>Active</h3>';
        $html .= '<p class="automation-feed-total">' . $automationActiveCount . '</p>';
        $html .= '</article>';
        $html .= '<article class="automation-feed-chip automation-feed-runs">';
        $html .= '<h3>Total runs</h3>';
        $html .= '<p class="automation-feed-total">' . $automationTotalRuns . '</p>';
        $html .= '</article>';
        $html .= '</div>';

        $html .= '<ul class="automation-feed-list">';
        foreach ($automationEntries as $automation) {
            $status = (string) ($automation['status'] ?? 'enabled');
            $statusLabel = $automationStatusLabels[$status] ?? ucwords(str_replace('_', ' ', $status));
            $trigger = (string) ($automation['trigger'] ?? ($automationTriggerOptions[0] ?? 'user_registered'));
            $triggerLabel = $automationTriggerLabels[$trigger] ?? ucwords(str_replace('_', ' ', $trigger));
            $priority = (string) ($automation['priority'] ?? ($automationPriorityOptions[0] ?? 'medium'));
            $priorityLabel = $automationPriorityLabels[$priority] ?? ucwords(str_replace('_', ' ', $priority));
            $runCount = (int) ($automation['run_count'] ?? 0);
            $runLimit = $automation['run_limit'] ?? null;
            $limitLabel = $runLimit === null ? 'Unlimited' : (int) $runLimit;
            $description = trim((string) ($automation['description'] ?? ''));
            $lastRunLabel = $automation['last_run_label'] ?? '';

            $statusClass = $automation['status_class'] ?? $automationStatusClass($status);
            $html .= '<li class="automation-feed-item automation-status-' . htmlspecialchars($statusClass) . '">';
            $html .= '<div class="automation-feed-header">';
            $html .= '<strong>' . htmlspecialchars($automation['name'] ?? 'Automation') . '</strong>';
            $html .= '<span class="automation-feed-status">' . htmlspecialchars($statusLabel) . '</span>';
            $html .= '</div>';
            $metaParts = [];
            $metaParts[] = 'Trigger: ' . $triggerLabel;
            $metaParts[] = 'Priority: ' . $priorityLabel;
            $metaParts[] = 'Runs: ' . $runCount;
            $metaParts[] = 'Limit: ' . $limitLabel;
            if ($lastRunLabel !== '') {
                $metaParts[] = 'Last run ' . $lastRunLabel;
            }
            $html .= '<p class="automation-feed-meta">' . implode(' · ', array_map('htmlspecialchars', $metaParts)) . '</p>';
            if ($description !== '') {
                $html .= '<p>' . htmlspecialchars($description) . '</p>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        if ($automationFeedHiddenCount > 0) {
            $hiddenLabel = $automationFeedHiddenCount === 1 ? 'automation not shown' : 'automations not shown';
            $html .= '<p class="automation-feed-footnote">' . htmlspecialchars((string) $automationFeedHiddenCount) . ' ' . htmlspecialchars($hiddenLabel) . '. Adjust the feed display limit in settings to see more.</p>';
        }
        $html .= '</section>';
    }

    $knowledgeContext = [
        'role' => $viewer['role'] ?? null,
        'user_id' => $viewer['id'] ?? null,
    ];
    $knowledgeEnabled = fg_get_asset_parameter_value('assets/php/render_knowledge_base.php', 'enabled', $knowledgeContext);
    $knowledgeArticles = [];
    $knowledgeCategories = [];
    if ($knowledgeEnabled) {
        $knowledgeCategories = fg_list_knowledge_categories($viewer);
        $categoryIndex = [];
        foreach ($knowledgeCategories as $category) {
            $categoryIndex[(int) ($category['id'] ?? 0)] = $category;
        }

        $knowledgeArticles = fg_filter_knowledge_articles($viewer);
        $defaultKnowledgeTag = trim((string) fg_get_asset_parameter_value('assets/php/render_knowledge_base.php', 'default_tag', $knowledgeContext));
        if ($defaultKnowledgeTag !== '') {
            $filterTag = strtolower($defaultKnowledgeTag);
            $knowledgeArticles = array_values(array_filter($knowledgeArticles, static function (array $article) use ($filterTag) {
                $tags = $article['tags'] ?? [];
                if (!is_array($tags)) {
                    return false;
                }
                return in_array($filterTag, array_map('strtolower', $tags), true);
            }));
        }

        $knowledgeLimitSetting = (int) fg_get_setting('knowledge_base_listing_limit', 5);
        $knowledgeOverrideLimit = (int) fg_get_asset_parameter_value('assets/php/render_knowledge_base.php', 'listing_limit', $knowledgeContext);
        $appliedLimit = $knowledgeOverrideLimit > 0 ? $knowledgeOverrideLimit : $knowledgeLimitSetting;
        if ($appliedLimit > 0) {
            $knowledgeArticles = array_slice($knowledgeArticles, 0, $appliedLimit);
        }
        $knowledgeCategoryIndex = $categoryIndex;
    } else {
        $knowledgeCategoryIndex = [];
    }

    if (!empty($knowledgeArticles)) {
        $knowledgeHeading = fg_translate('feed.knowledge.heading', ['user' => $viewer, 'default' => 'Knowledge base']);
        $html .= '<section class="panel knowledge-feed-panel">';
        $html .= '<h2>' . htmlspecialchars($knowledgeHeading) . '</h2>';
        $html .= '<p class="knowledge-panel-intro">Curated guides and reference articles served locally without relying on remote APIs.</p>';
        $html .= '<ul class="knowledge-feed-list">';
        foreach ($knowledgeArticles as $article) {
            $title = trim((string) ($article['title'] ?? 'Untitled article'));
            $summary = trim((string) ($article['summary'] ?? ''));
            $status = strtolower((string) ($article['status'] ?? 'published'));
            $visibility = strtolower((string) ($article['visibility'] ?? 'public'));
            $tags = $article['tags'] ?? [];
            if (!is_array($tags)) {
                $tags = [];
            }
            $updatedAt = (string) ($article['updated_at'] ?? $article['created_at'] ?? '');
            $updatedLabel = '';
            if ($updatedAt !== '') {
                $timestamp = strtotime($updatedAt);
                if ($timestamp) {
                    $updatedLabel = date('M j, Y', $timestamp);
                }
            }

            $html .= '<li class="knowledge-feed-item">';
            $html .= '<h3><a href="/knowledge.php?slug=' . urlencode((string) ($article['slug'] ?? '')) . '">' . htmlspecialchars($title) . '</a></h3>';
            $articleCategoryId = (int) ($article['category_id'] ?? 0);
            if ($articleCategoryId > 0 && isset($knowledgeCategoryIndex[$articleCategoryId])) {
                $category = $knowledgeCategoryIndex[$articleCategoryId];
                $categorySlug = strtolower((string) ($category['slug'] ?? ''));
                $categoryName = (string) ($category['name'] ?? '');
                if ($categoryName !== '') {
                    $html .= '<p class="knowledge-feed-category"><a href="/knowledge.php?category=' . urlencode($categorySlug) . '">' . htmlspecialchars($categoryName) . '</a></p>';
                }
            }
            if ($summary !== '') {
                $html .= '<p>' . htmlspecialchars($summary) . '</p>';
            }
            $metaParts = [];
            if ($updatedLabel !== '') {
                $metaParts[] = 'Updated ' . $updatedLabel;
            }
            if ($canModerate) {
                $metaParts[] = 'Status: ' . ucwords(str_replace('_', ' ', $status));
                $metaParts[] = 'Visibility: ' . ucfirst($visibility);
            }
            if (!empty($metaParts)) {
                $html .= '<p class="knowledge-feed-meta">' . htmlspecialchars(implode(' · ', $metaParts)) . '</p>';
            }
            if (!empty($tags)) {
                $html .= '<ul class="knowledge-feed-tags">';
                foreach ($tags as $tag) {
                    $tagSlug = strtolower((string) $tag);
                    $html .= '<li><a href="/knowledge.php?tag=' . urlencode($tagSlug) . '">' . htmlspecialchars((string) $tag) . '</a></li>';
                }
                $html .= '</ul>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '<footer class="knowledge-feed-footer"><a class="button secondary" href="/knowledge.php">Open knowledge base</a></footer>';
        $html .= '</section>';
    }

    $latestHeading = fg_translate('feed.latest_activity.heading', ['user' => $viewer, 'default' => 'Latest activity']);
    $html .= '<section class="panel"><h2>' . htmlspecialchars($latestHeading) . '</h2>';

    if (empty($records)) {
        $html .= '<p>No posts yet. Be the first to share something.</p>';
    }

    foreach ($records as $post) {
        $author = fg_find_user_by_id((int) ($post['author_id'] ?? 0));
        if ($author === null) {
            continue;
        }
        if (($post['privacy'] ?? 'public') === 'private' && (int) $author['id'] !== (int) $viewer['id']) {
            continue;
        }

        $html .= '<article class="feed-post" id="post-' . (int) $post['id'] . '" data-post-id="' . (int) $post['id'] . '">';
        $html .= '<header class="post-meta">';
        $html .= '<span><strong>' . htmlspecialchars($author['username']) . '</strong></span>';
        $html .= '<span>' . htmlspecialchars(date('M j, Y H:i', strtotime($post['created_at'] ?? 'now'))) . '</span>';
        if (!empty($post['custom_type'])) {
            $html .= '<span>Type: ' . htmlspecialchars($post['custom_type']) . '</span>';
        }
        $html .= '<span>Template: ' . htmlspecialchars($post['template'] ?? 'standard') . '</span>';
        $html .= '<span>Privacy: ' . htmlspecialchars($post['privacy'] ?? 'public') . '</span>';
        if (!empty($post['collaborators'])) {
            $html .= '<span>Collaborators: ' . htmlspecialchars(implode(', ', $post['collaborators'])) . '</span>';
        }
        $html .= '<span>Conversation: ' . htmlspecialchars($post['conversation_style'] ?? 'standard') . '</span>';
        if (!empty($post['tags'])) {
            $html .= '<span>Tags: ' . htmlspecialchars(implode(', ', $post['tags'])) . '</span>';
        }
        $html .= '</header>';
        $html .= fg_render_post_body($post);
        $likes = $post['likes'] ?? [];
        $liked = in_array($viewer['id'], $likes, true);
        $html .= '<div class="post-actions">';
        $html .= '<form method="post" action="/toggle-like.php" class="inline-form post-like-form" data-ajax="toggle-like">';
        $html .= '<input type="hidden" name="post_id" value="' . (int) $post['id'] . '">';
        $html .= '<button type="submit" data-like-button data-like-label-liked="Unlike" data-like-label-unliked="Like" data-liked="' . ($liked ? 'true' : 'false') . '"><span class="label">' . ($liked ? 'Unlike' : 'Like') . '</span><span class="count">' . count($likes) . '</span></button>';
        $html .= '</form>';
        if ((int) $author['id'] === (int) $viewer['id'] || in_array($viewer['username'], $post['collaborators'] ?? [], true)) {
            $html .= '<a href="/post.php?post=' . (int) $post['id'] . '">Edit</a>';
        }
        $html .= '</div>';
        $html .= '</article>';
    }

    $html .= '</section>';

    return $html;
}

