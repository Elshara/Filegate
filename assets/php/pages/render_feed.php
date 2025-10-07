<?php

require_once __DIR__ . '/../global/load_posts.php';
require_once __DIR__ . '/../global/find_user_by_id.php';
require_once __DIR__ . '/../global/get_setting.php';
require_once __DIR__ . '/../global/render_post_body.php';

function fg_render_feed(array $viewer): string
{
    $posts = fg_load_posts();
    $records = $posts['records'] ?? [];
    usort($records, static function ($a, $b) {
        return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
    });

    $html = '<section class="panel"><h1>Share something</h1>';
    $html .= '<form method="post" action="/post.php" class="post-composer" data-preview-target="#composer-preview" data-dataset-target="#composer-elements">';
    $html .= '<label>Content<textarea name="content" required data-preview-source></textarea></label>';
    if (fg_get_setting('post_custom_types', 'enabled') !== 'disabled') {
        $html .= '<label>Custom type<input type="text" name="custom_type" placeholder="article, gallery, event…"></label>';
    }
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
    $html .= '<label>Collaborators (usernames, comma separated)<input type="text" name="collaborators" placeholder="alex, taylor"></label>';
    $html .= '<details class="composer-help" data-dataset-name="html5_elements"><summary>HTML5 element support</summary><div class="composer-elements" id="composer-elements" data-dataset-output hidden></div><button type="button" class="dataset-viewer" data-dataset="html5_elements" data-expose="true" data-output="#composer-elements">Load supported elements</button></details>';
    $html .= '<button type="submit">Publish</button>';
    $html .= '</form></section>';
    $html .= '<section class="panel preview-panel" id="composer-preview" data-preview-output hidden><h2>Live preview</h2><div class="preview-body" data-preview-body><div class="preview-placeholder">Start writing to see your live preview, embeds, and statistics.</div></div><div class="preview-embeds" data-preview-embeds hidden></div><dl class="preview-statistics" data-preview-stats hidden></dl></section>';

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
        $html .= '<span>Privacy: ' . htmlspecialchars($post['privacy'] ?? 'public') . '</span>';
        if (!empty($post['collaborators'])) {
            $html .= '<span>Collaborators: ' . htmlspecialchars(implode(', ', $post['collaborators'])) . '</span>';
        }
        $html .= '<span>Conversation: ' . htmlspecialchars($post['conversation_style'] ?? 'standard') . '</span>';
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

