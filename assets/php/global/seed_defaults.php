<?php

require_once __DIR__ . '/load_users.php';
require_once __DIR__ . '/save_users.php';
require_once __DIR__ . '/load_posts.php';
require_once __DIR__ . '/save_posts.php';
require_once __DIR__ . '/load_settings.php';
require_once __DIR__ . '/save_settings.php';
require_once __DIR__ . '/load_uploads.php';
require_once __DIR__ . '/save_uploads.php';
require_once __DIR__ . '/load_notifications.php';
require_once __DIR__ . '/save_notifications.php';
require_once __DIR__ . '/load_themes.php';
require_once __DIR__ . '/save_themes.php';
require_once __DIR__ . '/dataset_path.php';
require_once __DIR__ . '/default_settings_dataset.php';
require_once __DIR__ . '/default_themes_dataset.php';

function fg_seed_defaults(): void
{
    $users = fg_load_users();
    if (!isset($users['records']) || !is_array($users['records'])) {
        $users = ['records' => [], 'next_id' => 1];
        fg_save_users($users);
    }

    $posts = fg_load_posts();
    if (!isset($posts['records']) || !is_array($posts['records'])) {
        $posts = ['records' => [], 'next_id' => 1];
        fg_save_posts($posts);
    }

    $uploads = fg_load_uploads();
    if (!isset($uploads['records']) || !is_array($uploads['records'])) {
        $uploads = ['records' => [], 'next_id' => 1];
    }
    if (!file_exists(fg_dataset_path('uploads'))) {
        fg_save_uploads($uploads);
    }

    $notifications = fg_load_notifications();
    if (!isset($notifications['records']) || !is_array($notifications['records'])) {
        $notifications = ['records' => [], 'next_id' => 1];
    }
    if (!file_exists(fg_dataset_path('notifications'))) {
        fg_save_notifications($notifications);
    }

    $settings = fg_load_settings();
    if (!isset($settings['settings']) || !is_array($settings['settings'])) {
        $settings = fg_default_settings_dataset();
        fg_save_settings($settings);
    }

    $themes = fg_load_themes();
    if (!isset($themes['records']) || !is_array($themes['records'])) {
        $themes = fg_default_themes_dataset();
        fg_save_themes($themes);
    }
}

