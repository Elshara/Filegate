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
require_once __DIR__ . '/load_project_status.php';
require_once __DIR__ . '/save_project_status.php';
require_once __DIR__ . '/default_project_status_dataset.php';
require_once __DIR__ . '/load_changelog.php';
require_once __DIR__ . '/save_changelog.php';
require_once __DIR__ . '/default_changelog_dataset.php';
require_once __DIR__ . '/load_feature_requests.php';
require_once __DIR__ . '/save_feature_requests.php';
require_once __DIR__ . '/default_feature_requests_dataset.php';
require_once __DIR__ . '/load_knowledge_base.php';
require_once __DIR__ . '/save_knowledge_base.php';
require_once __DIR__ . '/default_knowledge_base_dataset.php';
require_once __DIR__ . '/load_knowledge_categories.php';
require_once __DIR__ . '/save_knowledge_categories.php';
require_once __DIR__ . '/default_knowledge_categories_dataset.php';
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

    $projectStatus = fg_load_project_status();
    if (!isset($projectStatus['records']) || !is_array($projectStatus['records'])) {
        $projectStatus = fg_default_project_status_dataset();
        fg_save_project_status($projectStatus, 'Seed project status dataset', ['trigger' => 'seed_defaults']);
    }

    $changelog = fg_load_changelog();
    if (!isset($changelog['records']) || !is_array($changelog['records'])) {
        $changelog = fg_default_changelog_dataset();
        fg_save_changelog($changelog, 'Seed changelog dataset', ['trigger' => 'seed_defaults']);
    }

    $featureRequests = fg_load_feature_requests();
    if (!isset($featureRequests['records']) || !is_array($featureRequests['records'])) {
        $featureRequests = fg_default_feature_requests_dataset();
        fg_save_feature_requests($featureRequests, 'Seed feature request dataset', ['trigger' => 'seed_defaults']);
    }

    $knowledgeBase = fg_load_knowledge_base();
    if (!isset($knowledgeBase['records']) || !is_array($knowledgeBase['records'])) {
        $knowledgeBase = fg_default_knowledge_base_dataset();
        fg_save_knowledge_base($knowledgeBase, 'Seed knowledge base dataset', ['trigger' => 'seed_defaults']);
    }

    $knowledgeCategories = fg_load_knowledge_categories();
    if (!isset($knowledgeCategories['records']) || !is_array($knowledgeCategories['records'])) {
        $knowledgeCategories = fg_default_knowledge_categories_dataset();
        fg_save_knowledge_categories($knowledgeCategories, 'Seed knowledge category dataset', ['trigger' => 'seed_defaults']);
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

