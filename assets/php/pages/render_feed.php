<?php

require_once __DIR__ . '/../global/load_posts.php';
require_once __DIR__ . '/../global/find_user_by_id.php';
require_once __DIR__ . '/../global/get_setting.php';

function fg_render_feed(array $viewer): string
{
    $posts = fg_load_posts();
    $records = $posts['records'] ?? [];
    usort($records, static function ($a, $b) {
        return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
    });

    $html = '<section class="panel"><h1>Share something</h1>';
    $html .= '<form method="post" action="/post.php">';
    $html .= '<label>Content<textarea name="content" required></textarea></label>';
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
    $html .= '<button type="submit">Publish</button>';
    $html .= '</form></section>';

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

        $html .= '<article class="feed-post" id="post-' . (int) $post['id'] . '">';
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
        $html .= '<div class="post-content">' . ($post['content'] ?? '') . '</div>';
        $likes = $post['likes'] ?? [];
        $liked = in_array($viewer['id'], $likes, true);
        $html .= '<div class="post-actions">';
        $html .= '<form method="post" action="/toggle-like.php" class="inline-form">';
        $html .= '<input type="hidden" name="post_id" value="' . (int) $post['id'] . '">';
        $html .= '<button type="submit">' . ($liked ? 'Unlike' : 'Like') . ' (' . count($likes) . ')</button>';
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

