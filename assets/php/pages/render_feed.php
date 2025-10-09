<?php

require_once __DIR__ . '/../global/load_posts.php';
require_once __DIR__ . '/../global/find_user_by_id.php';
require_once __DIR__ . '/../global/get_setting.php';
require_once __DIR__ . '/../global/render_post_body.php';
require_once __DIR__ . '/../global/load_template_options.php';
require_once __DIR__ . '/../global/load_editor_options.php';
require_once __DIR__ . '/../global/load_notification_channels.php';
require_once __DIR__ . '/../global/translate.php';
require_once __DIR__ . '/../global/load_project_status.php';
require_once __DIR__ . '/../global/load_changelog.php';
require_once __DIR__ . '/../global/load_feature_requests.php';
require_once __DIR__ . '/../global/filter_knowledge_articles.php';
require_once __DIR__ . '/../global/list_knowledge_categories.php';
require_once __DIR__ . '/../global/get_asset_parameter_value.php';

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
    }

    if ($errorCode === 'feature-request-disabled') {
        $alerts[] = ['type' => 'error', 'message' => 'Feature requests are currently disabled.'];
    } elseif ($errorCode === 'feature-request-unauthorised') {
        $alerts[] = ['type' => 'error', 'message' => 'You do not have permission to manage that feature request.'];
    } elseif ($errorCode === 'feature-request-invalid') {
        $alerts[] = ['type' => 'error', 'message' => 'The requested feature entry could not be found.'];
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

    $knowledgeContext = [
        'role' => $viewer['role'] ?? null,
        'user_id' => $viewer['id'] ?? null,
    ];
    $knowledgeEnabled = fg_get_asset_parameter_value('assets/php/pages/render_knowledge_base.php', 'enabled', $knowledgeContext);
    $knowledgeArticles = [];
    $knowledgeCategories = [];
    if ($knowledgeEnabled) {
        $knowledgeCategories = fg_list_knowledge_categories($viewer);
        $categoryIndex = [];
        foreach ($knowledgeCategories as $category) {
            $categoryIndex[(int) ($category['id'] ?? 0)] = $category;
        }

        $knowledgeArticles = fg_filter_knowledge_articles($viewer);
        $defaultKnowledgeTag = trim((string) fg_get_asset_parameter_value('assets/php/pages/render_knowledge_base.php', 'default_tag', $knowledgeContext));
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
        $knowledgeOverrideLimit = (int) fg_get_asset_parameter_value('assets/php/pages/render_knowledge_base.php', 'listing_limit', $knowledgeContext);
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

