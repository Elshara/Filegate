<?php

require_once __DIR__ . '/../global/load_posts.php';
require_once __DIR__ . '/../global/sanitize_html.php';
require_once __DIR__ . '/../global/render_post_body.php';
require_once __DIR__ . '/../global/load_notification_channels.php';

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

    $channels = fg_load_notification_channels();
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
    if (!empty($profile_user['notification_preferences'])) {
        $labels = [];
        foreach ($profile_user['notification_preferences'] as $preference) {
            $labels[] = $channels[$preference]['label'] ?? ucfirst($preference);
        }
        $body .= '<p>Notifications: ' . htmlspecialchars(implode(', ', $labels)) . '</p>';
    }
    if (!empty($profile_user['cache_opt_in'])) {
        $body .= '<p>Local cache delivery enabled.</p>';
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
        $body .= '<fieldset class="inline-fieldset"><legend>Notification preferences</legend>';
        foreach ($channels as $key => $channel) {
            $checked = in_array($key, $profile_user['notification_preferences'] ?? [], true) ? ' checked' : '';
            $body .= '<label><input type="checkbox" name="notification_preferences[]" value="' . htmlspecialchars($key) . '"' . $checked . '> ' . htmlspecialchars($channel['label'] ?? $key) . '</label>';
        }
        $body .= '</fieldset>';
        $body .= '<label class="checkbox-label"><input type="checkbox" name="cache_opt_in" value="1"' . (!empty($profile_user['cache_opt_in']) ? ' checked' : '') . '> Enable local file cache delivery</label>';
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
        $body .= fg_render_post_body($post);
        $body .= '</article>';
    }
    $body .= '</section>';

    $body .= '</section>';

    return $body;
}

