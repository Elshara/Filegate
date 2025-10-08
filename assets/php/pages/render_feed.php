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

function fg_render_feed(array $viewer): string
{
    $posts = fg_load_posts();
    $records = $posts['records'] ?? [];
    usort($records, static function ($a, $b) {
        return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
    });

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
    $viewerRole = strtolower((string) ($viewer['role'] ?? ''));
    $isMember = !empty($viewer);
    $canViewPrivate = in_array($viewerRole, ['admin', 'moderator'], true);
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

