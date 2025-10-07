<?php

require_once __DIR__ . '/../global/load_posts.php';
require_once __DIR__ . '/../global/find_user_by_id.php';
require_once __DIR__ . '/../global/get_setting.php';
require_once __DIR__ . '/../global/render_post_body.php';
require_once __DIR__ . '/../global/load_template_options.php';
require_once __DIR__ . '/../global/load_editor_options.php';
require_once __DIR__ . '/../global/load_notification_channels.php';

function fg_render_feed(array $viewer): string
{
    $posts = fg_load_posts();
    $records = $posts['records'] ?? [];
    usort($records, static function ($a, $b) {
        return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
    });

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

    $html = '<section class="panel"><h1>Share something</h1>';
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

    $html .= '<section class="panel"><h2>Latest activity</h2>';

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

