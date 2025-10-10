<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/find_user_by_username.php';
require_once __DIR__ . '/upsert_user.php';
require_once __DIR__ . '/sanitize_html.php';
require_once __DIR__ . '/render_profile.php';
require_once __DIR__ . '/render_layout.php';
require_once __DIR__ . '/load_notification_channels.php';
require_once __DIR__ . '/guard_asset.php';

function fg_public_profile_controller(): void
{
    fg_bootstrap();
    $current = fg_require_login();
    fg_guard_asset('assets/php/profile_controller.php', [
        'role' => $current['role'] ?? null,
        'user_id' => $current['id'] ?? null,
    ]);

    $target_username = $_GET['user'] ?? $current['username'];
    $target = fg_find_user_by_username($target_username);

    if (!$target) {
        fg_render_layout('Profile not found', '<section class="panel"><h1>Profile not found</h1><p>The requested profile does not exist.</p></section>');
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int) $target['id'] === (int) $current['id']) {
        $payload = [
            'id' => $current['id'],
            'display_name' => trim($_POST['display_name'] ?? $current['display_name'] ?? ''),
            'bio' => fg_sanitize_html($_POST['bio'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'pronouns' => trim($_POST['pronouns'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'privacy' => $_POST['privacy'] ?? ($current['privacy'] ?? 'public'),
        ];
        $preferences = $_POST['notification_preferences'] ?? [];
        if (!is_array($preferences)) {
            $preferences = [$preferences];
        }
        $payload['notification_preferences'] = array_values(array_unique(array_filter(array_map('trim', $preferences))));
        $payload['cache_opt_in'] = isset($_POST['cache_opt_in']);
        $target = fg_upsert_user(array_merge($current, $payload));
        $current = $target;
    }

    $body = fg_render_profile_page($current, $target);
    fg_render_layout($target['display_name'] ?? $target['username'], $body);
}
