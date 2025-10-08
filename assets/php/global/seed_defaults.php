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
require_once __DIR__ . '/load_pages.php';
require_once __DIR__ . '/save_pages.php';
require_once __DIR__ . '/default_pages_dataset.php';
require_once __DIR__ . '/load_asset_snapshots.php';
require_once __DIR__ . '/save_asset_snapshots.php';
require_once __DIR__ . '/default_asset_snapshots_dataset.php';
require_once __DIR__ . '/load_activity_log.php';
require_once __DIR__ . '/save_activity_log.php';
require_once __DIR__ . '/default_activity_log_dataset.php';
require_once __DIR__ . '/load_translations.php';
require_once __DIR__ . '/save_translations.php';
require_once __DIR__ . '/default_translations_dataset.php';

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

    $pages = fg_load_pages();
    if (!isset($pages['records']) || !is_array($pages['records'])) {
        $pages = fg_default_pages_dataset();
        fg_save_pages($pages);
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

    $snapshots = fg_load_asset_snapshots();
    if (!isset($snapshots['records']) || !is_array($snapshots['records'])) {
        $snapshots = fg_default_asset_snapshots_dataset();
        fg_save_asset_snapshots($snapshots);
    }

    $activityLog = fg_load_activity_log();
    if (!isset($activityLog['records']) || !is_array($activityLog['records'])) {
        $activityLog = fg_default_activity_log_dataset();
        fg_save_activity_log($activityLog);
    }

    $translations = fg_load_translations();
    if (!isset($translations['locales']) || !is_array($translations['locales']) || empty($translations['locales'])) {
        $translations = fg_default_translations_dataset();
        fg_save_translations($translations, 'Seed translations', ['trigger' => 'seed_defaults']);
    }
}

