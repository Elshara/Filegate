<?php

require_once __DIR__ . '/../global/load_posts.php';
require_once __DIR__ . '/../global/sanitize_html.php';

function fg_render_profile_page(array $viewer, array $profile_user): string
{
    $posts = fg_load_posts();
    $records = array_filter($posts['records'] ?? [], static function ($post) use ($profile_user, $viewer) {
        if ((int) ($post['author_id'] ?? 0) !== (int) $profile_user['id']) {
            return false;
        }
        if (($post['privacy'] ?? 'public') === 'private' && (int) $viewer['id'] !== (int) $profile_user['id']) {
            return false;
        }
        return true;
    });

    usort($records, static function ($a, $b) {
        return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
    });

    $body = '<section class="panel profile-grid">';
    $body .= '<header>';
    $body .= '<h1>' . htmlspecialchars($profile_user['display_name'] ?? $profile_user['username']) . '</h1>';
    $body .= '<p>@' . htmlspecialchars($profile_user['username']) . '</p>';
    if (!empty($profile_user['website'])) {
        $body .= '<p><a href="' . htmlspecialchars($profile_user['website']) . '" rel="noopener">' . htmlspecialchars($profile_user['website']) . '</a></p>';
    }
    if (!empty($profile_user['pronouns'])) {
        $body .= '<p>Pronouns: ' . htmlspecialchars($profile_user['pronouns']) . '</p>';
    }
    if (!empty($profile_user['location'])) {
        $body .= '<p>Location: ' . htmlspecialchars($profile_user['location']) . '</p>';
    }
    $body .= '</header>';

    if (!empty($profile_user['bio'])) {
        $body .= '<article class="post-content">' . fg_sanitize_html($profile_user['bio']) . '</article>';
    }

    if ((int) $viewer['id'] === (int) $profile_user['id']) {
        $body .= '<section class="panel">';
        $body .= '<h2>Edit profile</h2>';
        $body .= '<form method="post" action="/profile.php">';
        $body .= '<label>Display name<input type="text" name="display_name" value="' . htmlspecialchars($profile_user['display_name'] ?? '') . '" required></label>';
        $body .= '<label>Bio<textarea name="bio">' . htmlspecialchars($profile_user['bio'] ?? '') . '</textarea></label>';
        $body .= '<label>Website<input type="url" name="website" value="' . htmlspecialchars($profile_user['website'] ?? '') . '"></label>';
        $body .= '<label>Pronouns<input type="text" name="pronouns" value="' . htmlspecialchars($profile_user['pronouns'] ?? '') . '"></label>';
        $body .= '<label>Location<input type="text" name="location" value="' . htmlspecialchars($profile_user['location'] ?? '') . '"></label>';
        $body .= '<label>Profile privacy<select name="privacy">';
        foreach (['public' => 'Public', 'connections' => 'Connections', 'private' => 'Private'] as $value => $label) {
            $selected = (($profile_user['privacy'] ?? 'public') === $value) ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></label>';
        $body .= '<button type="submit">Save profile</button>';
        $body .= '</form>';
        $body .= '</section>';
    }

    $body .= '<section class="panel">';
    $body .= '<h2>Posts</h2>';
    if (empty($records)) {
        $body .= '<p>No posts yet.</p>';
    }
    foreach ($records as $post) {
        $body .= '<article class="feed-post">';
        $body .= '<header class="post-meta">';
        $body .= '<span>' . htmlspecialchars(date('M j, Y H:i', strtotime($post['created_at'] ?? 'now'))) . '</span>';
        if (!empty($post['custom_type'])) {
            $body .= '<span>Type: ' . htmlspecialchars($post['custom_type']) . '</span>';
        }
        $body .= '<span>Privacy: ' . htmlspecialchars($post['privacy'] ?? 'public') . '</span>';
        $body .= '<span>Conversation: ' . htmlspecialchars($post['conversation_style'] ?? 'standard') . '</span>';
        $body .= '</header>';
        $body .= '<div class="post-content">' . ($post['content'] ?? '') . '</div>';
        $body .= '</article>';
    }
    $body .= '</section>';

    $body .= '</section>';

    return $body;
}

