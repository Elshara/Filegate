<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/is_admin.php';
require_once __DIR__ . '/load_asset_configurations.php';
require_once __DIR__ . '/load_asset_overrides.php';
require_once __DIR__ . '/update_asset_configuration.php';
require_once __DIR__ . '/update_asset_permissions.php';
require_once __DIR__ . '/update_asset_override.php';
require_once __DIR__ . '/clear_asset_override.php';
require_once __DIR__ . '/load_settings.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/load_users.php';
require_once __DIR__ . '/load_dataset_manifest.php';
require_once __DIR__ . '/dataset_format.php';
require_once __DIR__ . '/dataset_nature.php';
require_once __DIR__ . '/dataset_path.php';
require_once __DIR__ . '/ensure_data_directory.php';
require_once __DIR__ . '/load_dataset_contents.php';
require_once __DIR__ . '/save_dataset_contents.php';
require_once __DIR__ . '/dataset_default_payload.php';
require_once __DIR__ . '/format_file_size.php';
require_once __DIR__ . '/load_theme_tokens.php';
require_once __DIR__ . '/load_themes.php';
require_once __DIR__ . '/save_themes.php';
require_once __DIR__ . '/default_themes_dataset.php';
require_once __DIR__ . '/save_settings.php';
require_once __DIR__ . '/load_pages.php';
require_once __DIR__ . '/add_page.php';
require_once __DIR__ . '/update_page.php';
require_once __DIR__ . '/delete_page.php';
require_once __DIR__ . '/default_pages_dataset.php';
require_once __DIR__ . '/save_pages.php';
require_once __DIR__ . '/load_project_status.php';
require_once __DIR__ . '/default_project_status_dataset.php';
require_once __DIR__ . '/add_project_status.php';
require_once __DIR__ . '/update_project_status.php';
require_once __DIR__ . '/delete_project_status.php';
require_once __DIR__ . '/load_changelog.php';
require_once __DIR__ . '/default_changelog_dataset.php';
require_once __DIR__ . '/add_changelog_entry.php';
require_once __DIR__ . '/update_changelog_entry.php';
require_once __DIR__ . '/delete_changelog_entry.php';
require_once __DIR__ . '/load_feature_requests.php';
require_once __DIR__ . '/default_feature_requests_dataset.php';
require_once __DIR__ . '/add_feature_request.php';
require_once __DIR__ . '/update_feature_request.php';
require_once __DIR__ . '/delete_feature_request.php';
require_once __DIR__ . '/load_bug_reports.php';
require_once __DIR__ . '/default_bug_reports_dataset.php';
require_once __DIR__ . '/add_bug_report.php';
require_once __DIR__ . '/update_bug_report.php';
require_once __DIR__ . '/delete_bug_report.php';
require_once __DIR__ . '/load_polls.php';
require_once __DIR__ . '/default_polls_dataset.php';
require_once __DIR__ . '/add_poll.php';
require_once __DIR__ . '/update_poll.php';
require_once __DIR__ . '/delete_poll.php';
require_once __DIR__ . '/load_events.php';
require_once __DIR__ . '/default_events_dataset.php';
require_once __DIR__ . '/add_event.php';
require_once __DIR__ . '/update_event.php';
require_once __DIR__ . '/delete_event.php';
require_once __DIR__ . '/load_knowledge_base.php';
require_once __DIR__ . '/default_knowledge_base_dataset.php';
require_once __DIR__ . '/add_knowledge_article.php';
require_once __DIR__ . '/update_knowledge_article.php';
require_once __DIR__ . '/delete_knowledge_article.php';
require_once __DIR__ . '/load_knowledge_categories.php';
require_once __DIR__ . '/default_knowledge_categories_dataset.php';
require_once __DIR__ . '/add_knowledge_category.php';
require_once __DIR__ . '/update_knowledge_category.php';
require_once __DIR__ . '/delete_knowledge_category.php';
require_once __DIR__ . '/list_knowledge_categories.php';
require_once __DIR__ . '/load_content_modules.php';
require_once __DIR__ . '/save_content_modules.php';
require_once __DIR__ . '/default_content_modules_dataset.php';
require_once __DIR__ . '/add_content_module.php';
require_once __DIR__ . '/update_content_module.php';
require_once __DIR__ . '/delete_content_module.php';
require_once __DIR__ . '/load_content_blueprints.php';
require_once __DIR__ . '/load_automations.php';
require_once __DIR__ . '/default_automations_dataset.php';
require_once __DIR__ . '/add_automation.php';
require_once __DIR__ . '/update_automation.php';
require_once __DIR__ . '/delete_automation.php';
require_once __DIR__ . '/render_setup.php';
require_once __DIR__ . '/guard_asset.php';
require_once __DIR__ . '/load_asset_snapshots.php';
require_once __DIR__ . '/record_dataset_snapshot.php';
require_once __DIR__ . '/list_dataset_snapshots.php';
require_once __DIR__ . '/restore_dataset_snapshot.php';
require_once __DIR__ . '/delete_dataset_snapshot.php';
require_once __DIR__ . '/load_activity_log.php';
require_once __DIR__ . '/load_translations.php';
require_once __DIR__ . '/save_translations.php';
require_once __DIR__ . '/normalize_translation_token_key.php';
require_once __DIR__ . '/default_translations_dataset.php';

function fg_public_setup_controller(): void
{
    fg_bootstrap();
    $current = fg_require_login();
    fg_guard_asset('assets/php/setup_controller.php', [
        'role' => $current['role'] ?? null,
        'user_id' => $current['id'] ?? null,
    ]);

    if (!$current || !fg_is_admin($current)) {
        http_response_code(403);
        fg_render_setup_page([
            'message' => '',
            'errors' => ['Administrator permissions are required to access the setup dashboard.'],
            'configurations' => fg_load_asset_configurations(),
            'overrides' => fg_load_asset_overrides(),
            'roles' => (fg_load_settings()['role_definitions'] ?? []),
            'users' => (fg_load_users()['records'] ?? []),
            'project_status' => fg_default_project_status_dataset(),
            'changelog' => fg_default_changelog_dataset(),
        ]);
        return;
    }

    $message = '';
    $errors = [];
    fg_ensure_data_directory();
    $manifest = fg_load_dataset_manifest();
    $datasets = $manifest;
    $translations = fg_load_translations();
    try {
        $projectStatus = fg_load_project_status();
        if (!isset($projectStatus['records']) || !is_array($projectStatus['records'])) {
            $projectStatus = fg_default_project_status_dataset();
        }
    } catch (Throwable $exception) {
        $errors[] = 'Unable to load project status dataset: ' . $exception->getMessage();
        $projectStatus = fg_default_project_status_dataset();
    }

    try {
        $changelog = fg_load_changelog();
        if (!isset($changelog['records']) || !is_array($changelog['records'])) {
            $changelog = fg_default_changelog_dataset();
        }
    } catch (Throwable $exception) {
        $errors[] = 'Unable to load changelog dataset: ' . $exception->getMessage();
        $changelog = fg_default_changelog_dataset();
    }

    try {
        $featureRequests = fg_load_feature_requests();
        if (!isset($featureRequests['records']) || !is_array($featureRequests['records'])) {
            $featureRequests = fg_default_feature_requests_dataset();
        }
    } catch (Throwable $exception) {
        $errors[] = 'Unable to load feature request dataset: ' . $exception->getMessage();
        $featureRequests = fg_default_feature_requests_dataset();
    }

    try {
        $bugReports = fg_load_bug_reports();
        if (!isset($bugReports['records']) || !is_array($bugReports['records'])) {
            $bugReports = fg_default_bug_reports_dataset();
        }
    } catch (Throwable $exception) {
        $errors[] = 'Unable to load bug report dataset: ' . $exception->getMessage();
        $bugReports = fg_default_bug_reports_dataset();
    }

    try {
        $polls = fg_load_polls();
        if (!isset($polls['records']) || !is_array($polls['records'])) {
            $polls = fg_default_polls_dataset();
        }
    } catch (Throwable $exception) {
        $errors[] = 'Unable to load poll dataset: ' . $exception->getMessage();
        $polls = fg_default_polls_dataset();
    }

    try {
        $events = fg_load_events();
        if (!isset($events['records']) || !is_array($events['records'])) {
            $events = fg_default_events_dataset();
        }
    } catch (Throwable $exception) {
        $errors[] = 'Unable to load event dataset: ' . $exception->getMessage();
        $events = fg_default_events_dataset();
    }

    try {
        $knowledgeBase = fg_load_knowledge_base();
        if (!isset($knowledgeBase['records']) || !is_array($knowledgeBase['records'])) {
            $knowledgeBase = fg_default_knowledge_base_dataset();
        }
    } catch (Throwable $exception) {
        $errors[] = 'Unable to load knowledge base dataset: ' . $exception->getMessage();
        $knowledgeBase = fg_default_knowledge_base_dataset();
    }

    try {
        $knowledgeCategories = fg_load_knowledge_categories();
        if (!isset($knowledgeCategories['records']) || !is_array($knowledgeCategories['records'])) {
            $knowledgeCategories = fg_default_knowledge_categories_dataset();
        }
    } catch (Throwable $exception) {
        $errors[] = 'Unable to load knowledge category dataset: ' . $exception->getMessage();
        $knowledgeCategories = fg_default_knowledge_categories_dataset();
    }

    try {
        $contentModules = fg_load_content_modules();
        if (!isset($contentModules['records']) || !is_array($contentModules['records'])) {
            $contentModules = fg_default_content_modules_dataset();
        }
    } catch (Throwable $exception) {
        $errors[] = 'Unable to load content module dataset: ' . $exception->getMessage();
        $contentModules = fg_default_content_modules_dataset();
    }

    $contentBlueprints = fg_load_content_blueprints();

    try {
        $automations = fg_load_automations();
        if (!isset($automations['records']) || !is_array($automations['records'])) {
            $automations = fg_default_automations_dataset();
        }
    } catch (Throwable $exception) {
        $errors[] = 'Unable to load automation dataset: ' . $exception->getMessage();
        $automations = fg_default_automations_dataset();
    }

    $featureRequestStatusOptions = fg_get_setting('feature_request_statuses', ['open', 'researching', 'planned', 'in_progress', 'completed', 'declined']);
    if (!is_array($featureRequestStatusOptions) || empty($featureRequestStatusOptions)) {
        $featureRequestStatusOptions = ['open'];
    }
    $featureRequestStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $featureRequestStatusOptions)));
    if (empty($featureRequestStatusOptions)) {
        $featureRequestStatusOptions = ['open'];
    }

    $featureRequestPriorityOptions = fg_get_setting('feature_request_priorities', ['low', 'medium', 'high', 'critical']);
    if (!is_array($featureRequestPriorityOptions) || empty($featureRequestPriorityOptions)) {
        $featureRequestPriorityOptions = ['medium'];
    }
    $featureRequestPriorityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $featureRequestPriorityOptions)));
    if (empty($featureRequestPriorityOptions)) {
        $featureRequestPriorityOptions = ['medium'];
    }

    $featureRequestDefaultVisibility = strtolower((string) fg_get_setting('feature_request_default_visibility', 'members'));
    if (!in_array($featureRequestDefaultVisibility, ['public', 'members', 'private'], true)) {
        $featureRequestDefaultVisibility = 'members';
    }

    $featureRequestPolicy = strtolower((string) fg_get_setting('feature_request_policy', 'members'));
    if ($featureRequestPolicy === 'enabled') {
        $featureRequestPolicy = 'members';
    }

    $bugStatusOptions = fg_get_setting('bug_report_statuses', ['new', 'triaged', 'in_progress', 'resolved', 'wont_fix', 'duplicate']);
    if (!is_array($bugStatusOptions) || empty($bugStatusOptions)) {
        $bugStatusOptions = ['new'];
    }
    $bugStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $bugStatusOptions)));
    if (empty($bugStatusOptions)) {
        $bugStatusOptions = ['new'];
    }

    $bugSeverityOptions = fg_get_setting('bug_report_severities', ['low', 'medium', 'high', 'critical']);
    if (!is_array($bugSeverityOptions) || empty($bugSeverityOptions)) {
        $bugSeverityOptions = ['medium'];
    }
    $bugSeverityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $bugSeverityOptions)));
    if (empty($bugSeverityOptions)) {
        $bugSeverityOptions = ['medium'];
    }

    $bugDefaultVisibility = strtolower((string) fg_get_setting('bug_report_default_visibility', 'members'));
    if (!in_array($bugDefaultVisibility, ['public', 'members', 'private'], true)) {
        $bugDefaultVisibility = 'members';
    }

    $bugPolicy = strtolower((string) fg_get_setting('bug_report_policy', 'members'));
    if ($bugPolicy === 'enabled') {
        $bugPolicy = 'members';
    }
    if (!in_array($bugPolicy, ['disabled', 'members', 'moderators', 'admins'], true)) {
        $bugPolicy = 'members';
    }

    $bugDefaultOwnerRole = trim((string) fg_get_setting('bug_report_default_owner_role', 'moderator'));
    if ($bugDefaultOwnerRole === '') {
        $bugDefaultOwnerRole = 'moderator';
    }

    $bugFeedDisplayLimit = (int) fg_get_setting('bug_report_feed_display_limit', 5);
    if ($bugFeedDisplayLimit < 1) {
        $bugFeedDisplayLimit = 5;
    }

    $pollStatusOptions = fg_get_setting('poll_statuses', ['draft', 'open', 'closed']);
    if (!is_array($pollStatusOptions) || empty($pollStatusOptions)) {
        $pollStatusOptions = ['draft', 'open', 'closed'];
    }
    $pollStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $pollStatusOptions)));
    if (empty($pollStatusOptions)) {
        $pollStatusOptions = ['draft', 'open', 'closed'];
    }

    $pollDefaultVisibility = strtolower((string) fg_get_setting('poll_default_visibility', 'members'));
    if (!in_array($pollDefaultVisibility, ['public', 'members', 'private'], true)) {
        $pollDefaultVisibility = 'members';
    }

    $pollPolicy = strtolower((string) fg_get_setting('poll_policy', 'moderators'));
    if ($pollPolicy === 'enabled') {
        $pollPolicy = 'members';
    }
    if (!in_array($pollPolicy, ['disabled', 'members', 'moderators', 'admins'], true)) {
        $pollPolicy = 'moderators';
    }

    $pollAllowMultipleDefault = (bool) fg_get_setting('poll_allow_multiple_default', false);

    $eventStatusOptions = fg_get_setting('event_statuses', ['draft', 'scheduled', 'completed', 'cancelled']);
    if (!is_array($eventStatusOptions) || empty($eventStatusOptions)) {
        $eventStatusOptions = ['draft', 'scheduled', 'completed', 'cancelled'];
    }
    $eventStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $eventStatusOptions)));
    if (empty($eventStatusOptions)) {
        $eventStatusOptions = ['draft'];
    }

    $eventDefaultVisibility = strtolower((string) fg_get_setting('event_default_visibility', 'members'));
    if (!in_array($eventDefaultVisibility, ['public', 'members', 'private'], true)) {
        $eventDefaultVisibility = 'members';
    }

    $eventPolicy = strtolower((string) fg_get_setting('event_policy', 'moderators'));
    if ($eventPolicy === 'enabled') {
        $eventPolicy = 'members';
    }
    if (!in_array($eventPolicy, ['disabled', 'members', 'moderators', 'admins'], true)) {
        $eventPolicy = 'moderators';
    }

    $eventRsvpPolicy = strtolower((string) fg_get_setting('event_rsvp_policy', 'members'));
    if (!in_array($eventRsvpPolicy, ['public', 'members', 'private'], true)) {
        $eventRsvpPolicy = 'members';
    }

    $eventDefaultTimezone = trim((string) fg_get_setting('event_default_timezone', 'UTC'));
    if ($eventDefaultTimezone === '') {
        $eventDefaultTimezone = 'UTC';
    }

    $eventFeedDisplayLimit = (int) fg_get_setting('event_feed_display_limit', 4);
    if ($eventFeedDisplayLimit < 1) {
        $eventFeedDisplayLimit = 4;
    }

    $knowledgeDefaultStatus = strtolower((string) fg_get_setting('knowledge_base_default_status', 'published'));
    if (!in_array($knowledgeDefaultStatus, ['draft', 'scheduled', 'published', 'archived'], true)) {
        $knowledgeDefaultStatus = 'published';
    }

    $knowledgeDefaultVisibility = strtolower((string) fg_get_setting('knowledge_base_visibility_policy', 'public'));
    if (!in_array($knowledgeDefaultVisibility, ['public', 'members', 'private'], true)) {
        $knowledgeDefaultVisibility = 'public';
    }

    $knowledgeDefaultCategoryId = (int) fg_get_setting('knowledge_base_default_category', 0);
    if ($knowledgeDefaultCategoryId <= 0) {
        $knowledgeDefaultCategoryId = null;
    }

    $automationStatusOptions = fg_get_setting('automation_statuses', ['enabled', 'paused', 'disabled']);
    if (!is_array($automationStatusOptions) || empty($automationStatusOptions)) {
        $automationStatusOptions = ['enabled', 'paused', 'disabled'];
    }
    $automationStatusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $automationStatusOptions)));
    if (empty($automationStatusOptions)) {
        $automationStatusOptions = ['enabled'];
    }

    $automationDefaultStatus = strtolower((string) fg_get_setting('automation_default_status', $automationStatusOptions[0]));
    if (!in_array($automationDefaultStatus, $automationStatusOptions, true)) {
        $automationDefaultStatus = $automationStatusOptions[0];
    }

    $automationTriggerOptions = fg_get_setting('automation_triggers', ['user_registered', 'post_published', 'feature_request_submitted', 'bug_report_created']);
    if (!is_array($automationTriggerOptions) || empty($automationTriggerOptions)) {
        $automationTriggerOptions = ['user_registered'];
    }
    $automationTriggerOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $automationTriggerOptions)));
    if (empty($automationTriggerOptions)) {
        $automationTriggerOptions = ['user_registered'];
    }

    $automationActionTypes = fg_get_setting('automation_action_types', ['enqueue_notification', 'record_activity', 'update_dataset']);
    if (!is_array($automationActionTypes) || empty($automationActionTypes)) {
        $automationActionTypes = ['enqueue_notification'];
    }
    $automationActionTypes = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $automationActionTypes)));
    if (empty($automationActionTypes)) {
        $automationActionTypes = ['enqueue_notification'];
    }

    $automationConditionTypes = fg_get_setting('automation_condition_types', ['custom', 'role_equals', 'dataset_threshold', 'time_window']);
    if (!is_array($automationConditionTypes) || empty($automationConditionTypes)) {
        $automationConditionTypes = ['custom'];
    }
    $automationConditionTypes = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $automationConditionTypes)));
    if (empty($automationConditionTypes)) {
        $automationConditionTypes = ['custom'];
    }

    $automationPriorityOptions = fg_get_setting('automation_priority_options', ['low', 'medium', 'high']);
    if (!is_array($automationPriorityOptions) || empty($automationPriorityOptions)) {
        $automationPriorityOptions = ['low', 'medium', 'high'];
    }
    $automationPriorityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $automationPriorityOptions)));
    if (empty($automationPriorityOptions)) {
        $automationPriorityOptions = ['medium'];
    }

    $automationDefaultOwnerRole = trim((string) fg_get_setting('automation_default_owner_role', 'admin'));
    if ($automationDefaultOwnerRole === '') {
        $automationDefaultOwnerRole = 'admin';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action_override'] ?? ($_POST['action'] ?? '');
        if (in_array($action, ['save_dataset', 'reset_dataset', 'create_snapshot', 'restore_snapshot', 'delete_snapshot'], true)) {
            $dataset = $_POST['dataset'] ?? '';
            if ($dataset === '' || !isset($manifest[$dataset])) {
                $errors[] = 'Unknown dataset supplied.';
            } else {
                if ($action === 'save_dataset') {
                    $payload = '';
                    $file = $_FILES['dataset_file'] ?? null;
                    $fileError = is_array($file) ? (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
                    if ($file && $fileError === UPLOAD_ERR_OK && isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
                        $fileContents = file_get_contents($file['tmp_name']);
                        if ($fileContents === false) {
                            $errors[] = 'Unable to read uploaded dataset file.';
                        } else {
                            $payload = (string) $fileContents;
                        }
                    } else {
                        $payload = (string) ($_POST['dataset_payload'] ?? '');
                    }

                    if (trim($payload) === '') {
                        $errors[] = 'Dataset payload cannot be empty.';
                    } else {
                        try {
                            fg_save_dataset_contents(
                                $dataset,
                                $payload,
                                'Setup dashboard save',
                                [
                                    'trigger' => 'setup_dashboard',
                                    'performed_by' => $current['id'] ?? null,
                                ]
                            );
                            $message = 'Dataset ' . $dataset . ' saved successfully.';
                        } catch (Throwable $exception) {
                            $errors[] = $exception->getMessage();
                        }
                    }
                } elseif ($action === 'reset_dataset') {
                    $defaultPayload = fg_dataset_default_payload($dataset);
                    if ($defaultPayload === null) {
                        $errors[] = 'This dataset does not define automatic defaults.';
                    } else {
                        try {
                            fg_save_dataset_contents(
                                $dataset,
                                $defaultPayload,
                                'Dataset reset',
                                [
                                    'trigger' => 'setup_dashboard',
                                    'performed_by' => $current['id'] ?? null,
                                    'reset_to_defaults' => true,
                                ]
                            );
                            $message = 'Dataset ' . $dataset . ' has been reset to its defaults.';
                        } catch (Throwable $exception) {
                            $errors[] = $exception->getMessage();
                        }
                    }
                } elseif ($action === 'create_snapshot') {
                    $labelInput = trim((string) ($_POST['snapshot_label'] ?? ''));
                    $label = $labelInput === '' ? 'Manual snapshot' : $labelInput;
                    try {
                        $payload = fg_load_dataset_contents($dataset);
                        if ($payload === '') {
                            $errors[] = 'Dataset is empty. Generate contents before recording a snapshot.';
                        } else {
                            $created = fg_record_dataset_snapshot(
                                $dataset,
                                $label,
                                [
                                    'trigger' => 'manual',
                                    'source' => 'setup_dashboard',
                                    'created_by' => $current['id'] ?? null,
                                ],
                                $payload
                            );
                            if ($created) {
                                $message = 'Snapshot recorded for ' . $dataset . '.';
                            } else {
                                $errors[] = 'Snapshot not recorded because an identical copy already exists.';
                            }
                        }
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                } elseif ($action === 'restore_snapshot') {
                    $snapshotId = (int) ($_POST['snapshot_id'] ?? 0);
                    if ($snapshotId <= 0) {
                        $errors[] = 'Snapshot identifier is required to restore a dataset.';
                    } else {
                        try {
                            fg_restore_dataset_snapshot(
                                $dataset,
                                $snapshotId,
                                [
                                    'restored_by' => $current['id'] ?? null,
                                    'trigger' => 'setup_dashboard',
                                ]
                            );
                            $message = 'Snapshot restored for ' . $dataset . '.';
                        } catch (Throwable $exception) {
                            $errors[] = $exception->getMessage();
                        }
                    }
                } elseif ($action === 'delete_snapshot') {
                    $snapshotId = (int) ($_POST['snapshot_id'] ?? 0);
                    if ($snapshotId <= 0) {
                        $errors[] = 'Snapshot identifier missing for deletion.';
                    } else {
                        try {
                            $removed = fg_delete_dataset_snapshot($snapshotId, $dataset, [
                                'trigger' => 'setup_dashboard',
                                'performed_by' => $current['id'] ?? null,
                            ]);
                            if ($removed) {
                                $message = 'Snapshot removed.';
                            } else {
                                $errors[] = 'Snapshot not found or already removed.';
                            }
                        } catch (Throwable $exception) {
                            $errors[] = $exception->getMessage();
                        }
                    }
                }
                if ($dataset === 'translations') {
                    $translations = fg_load_translations();
                }
            }
        } elseif (in_array($action, [
            'translation_create_locale',
            'translation_save_locale',
            'translation_delete_locale',
            'translation_set_fallback',
            'translation_create_token',
            'translation_update_token',
            'translation_delete_token',
        ], true)) {
            $translations = fg_load_translations();
            $locales = $translations['locales'] ?? [];
            $tokens = $translations['tokens'] ?? [];
            $fallbackLocale = $translations['fallback_locale'] ?? 'en';
            $defaultTokens = fg_default_translations_dataset()['tokens'] ?? [];

            if ($action === 'translation_create_locale') {
                $keyRaw = (string) ($_POST['locale_key'] ?? '');
                $key = strtolower(preg_replace('/[^a-z0-9_-]/', '', $keyRaw));
                if ($key === '') {
                    $errors[] = 'Locale key is required.';
                } elseif (isset($locales[$key])) {
                    $errors[] = 'Locale already exists.';
                } else {
                    $label = trim((string) ($_POST['locale_label'] ?? ''));
                    if ($label === '') {
                        $label = strtoupper($key);
                    }
                    $sourceKey = (string) ($_POST['copy_from'] ?? $fallbackLocale);
                    if (!isset($locales[$sourceKey])) {
                        $sourceKey = $fallbackLocale;
                    }
                    $sourceStrings = $locales[$sourceKey]['strings'] ?? [];
                    $strings = [];
                    foreach ($tokens as $tokenKey => $definition) {
                        $strings[$tokenKey] = $sourceStrings[$tokenKey] ?? '';
                    }
                    $translations['locales'][$key] = [
                        'label' => $label,
                        'strings' => $strings,
                    ];
                    try {
                        fg_save_translations($translations, 'Create locale', [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_dashboard',
                        ]);
                        $translations = fg_load_translations();
                        $message = 'Locale ' . $key . ' created.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'translation_save_locale') {
                $localeKey = (string) ($_POST['locale'] ?? '');
                if (!isset($locales[$localeKey])) {
                    $errors[] = 'Locale not found.';
                } else {
                    $label = trim((string) ($_POST['locale_label'] ?? ''));
                    if ($label === '') {
                        $label = $locales[$localeKey]['label'] ?? strtoupper($localeKey);
                    }
                    $stringsInput = $_POST['strings'] ?? [];
                    if (!is_array($stringsInput)) {
                        $stringsInput = [];
                    }
                    $strings = [];
                    foreach ($tokens as $tokenKey => $definition) {
                        $value = $stringsInput[$tokenKey] ?? ($locales[$localeKey]['strings'][$tokenKey] ?? '');
                        $strings[$tokenKey] = (string) $value;
                    }
                    $translations['locales'][$localeKey]['label'] = $label;
                    $translations['locales'][$localeKey]['strings'] = $strings;
                    try {
                        fg_save_translations($translations, 'Update locale', [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_dashboard',
                            'locale' => $localeKey,
                        ]);
                        $translations = fg_load_translations();
                        $message = 'Locale ' . $localeKey . ' updated.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'translation_delete_locale') {
                $localeKey = (string) ($_POST['locale'] ?? '');
                if (!isset($locales[$localeKey])) {
                    $errors[] = 'Locale not found.';
                } elseif (count($locales) <= 1) {
                    $errors[] = 'At least one locale must remain registered.';
                } else {
                    unset($translations['locales'][$localeKey]);
                    if (($translations['fallback_locale'] ?? '') === $localeKey) {
                        $translations['fallback_locale'] = array_key_first($translations['locales']);
                    }
                    try {
                        fg_save_translations($translations, 'Delete locale', [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_dashboard',
                            'locale' => $localeKey,
                        ]);
                        $translations = fg_load_translations();
                        $message = 'Locale ' . $localeKey . ' removed.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'translation_set_fallback') {
                $localeKey = (string) ($_POST['locale'] ?? '');
                if (!isset($locales[$localeKey])) {
                    $errors[] = 'Locale not found.';
                } else {
                    $translations['fallback_locale'] = $localeKey;
                    try {
                        fg_save_translations($translations, 'Set fallback locale', [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_dashboard',
                        ]);
                        $translations = fg_load_translations();
                        $message = 'Fallback locale updated.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'translation_create_token') {
                $tokenKeyRaw = (string) ($_POST['token_key'] ?? '');
                $tokenKey = fg_normalize_translation_token_key($tokenKeyRaw);
                if ($tokenKey === '') {
                    $errors[] = 'Token key is required.';
                } elseif (isset($tokens[$tokenKey])) {
                    $errors[] = 'Token already exists.';
                } else {
                    $label = trim((string) ($_POST['token_label'] ?? ''));
                    if ($label === '') {
                        $label = ucwords(str_replace(['.', '_', '-'], ' ', $tokenKey));
                    }
                    $description = trim((string) ($_POST['token_description'] ?? ''));
                    $tokens[$tokenKey] = [
                        'label' => $label,
                        'description' => $description,
                    ];
                    $translations['tokens'] = $tokens;
                    foreach ($locales as $localeKey => $definition) {
                        if (!isset($translations['locales'][$localeKey]['strings']) || !is_array($translations['locales'][$localeKey]['strings'])) {
                            $translations['locales'][$localeKey]['strings'] = [];
                        }
                        if (!array_key_exists($tokenKey, $translations['locales'][$localeKey]['strings'])) {
                            $translations['locales'][$localeKey]['strings'][$tokenKey] = '';
                        }
                    }
                    ksort($translations['tokens']);
                    try {
                        fg_save_translations($translations, 'Create translation token', [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_dashboard',
                            'token' => $tokenKey,
                        ]);
                        $translations = fg_load_translations();
                        $message = 'Translation token ' . $tokenKey . ' created.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'translation_update_token') {
                $tokenKey = (string) ($_POST['token_key'] ?? '');
                if (!isset($tokens[$tokenKey])) {
                    $errors[] = 'Token not found.';
                } else {
                    $label = trim((string) ($_POST['token_label'] ?? ''));
                    if ($label === '') {
                        $label = $tokens[$tokenKey]['label'] ?? ucwords(str_replace(['.', '_', '-'], ' ', $tokenKey));
                    }
                    $description = trim((string) ($_POST['token_description'] ?? ''));
                    $tokens[$tokenKey]['label'] = $label;
                    $tokens[$tokenKey]['description'] = $description;
                    $translations['tokens'] = $tokens;

                    $fillValue = (string) ($_POST['fill_value'] ?? '');
                    $fillMode = (string) ($_POST['fill_mode'] ?? '');
                    if ($fillMode === 'missing' || $fillMode === 'all') {
                        foreach ($locales as $localeKey => $definition) {
                            $existing = $translations['locales'][$localeKey]['strings'][$tokenKey] ?? '';
                            $shouldUpdate = ($fillMode === 'all') || ($existing === '');
                            if ($shouldUpdate) {
                                $translations['locales'][$localeKey]['strings'][$tokenKey] = $fillValue;
                            }
                        }
                    }
                    ksort($translations['tokens']);

                    try {
                        fg_save_translations($translations, 'Update translation token', [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_dashboard',
                            'token' => $tokenKey,
                            'fill_mode' => $fillMode,
                        ]);
                        $translations = fg_load_translations();
                        $message = 'Translation token ' . $tokenKey . ' updated.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'translation_delete_token') {
                $tokenKey = (string) ($_POST['token_key'] ?? '');
                if (!isset($tokens[$tokenKey])) {
                    $errors[] = 'Token not found.';
                } elseif (isset($defaultTokens[$tokenKey])) {
                    $errors[] = 'Seeded translation tokens cannot be deleted.';
                } else {
                    unset($tokens[$tokenKey]);
                    $translations['tokens'] = $tokens;
                    foreach ($locales as $localeKey => $definition) {
                        if (isset($translations['locales'][$localeKey]['strings'][$tokenKey])) {
                            unset($translations['locales'][$localeKey]['strings'][$tokenKey]);
                        }
                    }
                    ksort($translations['tokens']);
                    try {
                        fg_save_translations($translations, 'Delete translation token', [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_dashboard',
                            'token' => $tokenKey,
                        ]);
                        $translations = fg_load_translations();
                        $message = 'Translation token ' . $tokenKey . ' removed.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            }
        } elseif (in_array($action, ['create_theme', 'update_theme', 'delete_theme', 'set_default_theme'], true)) {
            $themes = fg_load_themes();
            if (!isset($themes['records']) || !is_array($themes['records'])) {
                $themes = fg_default_themes_dataset();
                fg_save_themes($themes);
            }
            $tokenDefinitions = fg_load_theme_tokens()['tokens'] ?? [];
            $records = $themes['records'] ?? [];

            if ($action === 'create_theme') {
                $themeKeyRaw = (string) ($_POST['theme_key'] ?? '');
                $themeKey = strtolower(preg_replace('/[^a-z0-9_-]/', '', $themeKeyRaw));
                if ($themeKey === '') {
                    $errors[] = 'Theme key is required.';
                } elseif (isset($records[$themeKey])) {
                    $errors[] = 'A theme with that key already exists.';
                } else {
                    $label = trim((string) ($_POST['label'] ?? ''));
                    if ($label === '') {
                        $label = ucwords(str_replace(['_', '-'], ' ', $themeKey));
                    }
                    $description = trim((string) ($_POST['description'] ?? ''));
                    $tokensInput = $_POST['tokens'] ?? [];
                    if (!is_array($tokensInput)) {
                        $tokensInput = [];
                    }
                    $values = [];
                    foreach ($tokenDefinitions as $tokenKey => $definition) {
                        $type = $definition['type'] ?? 'text';
                        $default = $definition['default'] ?? '';
                        $value = $tokensInput[$tokenKey] ?? $default;
                        if ($type === 'color') {
                            $valueString = (string) $value;
                            if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $valueString)) {
                                $valueString = $default;
                            }
                            $value = $valueString;
                        } else {
                            $value = trim((string) $value);
                        }
                        $values[$tokenKey] = $value;
                    }
                    $records[$themeKey] = [
                        'label' => $label,
                        'description' => $description,
                        'tokens' => $values,
                    ];
                    $themes['records'] = $records;
                    if (!isset($themes['metadata']['default']) || !isset($records[$themes['metadata']['default']])) {
                        $themes['metadata']['default'] = $themeKey;
                    }
                    fg_save_themes($themes);
                    $message = 'Theme created successfully.';
                }
            } elseif ($action === 'update_theme') {
                $themeKey = (string) ($_POST['theme_key'] ?? '');
                if (!isset($records[$themeKey])) {
                    $errors[] = 'Unknown theme specified.';
                } else {
                    $label = trim((string) ($_POST['label'] ?? ''));
                    if ($label === '') {
                        $label = $records[$themeKey]['label'] ?? ucwords(str_replace(['_', '-'], ' ', $themeKey));
                    }
                    $description = trim((string) ($_POST['description'] ?? ''));
                    $tokensInput = $_POST['tokens'] ?? [];
                    if (!is_array($tokensInput)) {
                        $tokensInput = [];
                    }
                    $values = [];
                    foreach ($tokenDefinitions as $tokenKey => $definition) {
                        $type = $definition['type'] ?? 'text';
                        $default = $definition['default'] ?? '';
                        $value = $tokensInput[$tokenKey] ?? ($records[$themeKey]['tokens'][$tokenKey] ?? $default);
                        if ($type === 'color') {
                            $valueString = (string) $value;
                            if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $valueString)) {
                                $valueString = $default;
                            }
                            $value = $valueString;
                        } else {
                            $value = trim((string) $value);
                        }
                        $values[$tokenKey] = $value;
                    }
                    $records[$themeKey]['label'] = $label;
                    $records[$themeKey]['description'] = $description;
                    $records[$themeKey]['tokens'] = $values;
                    $themes['records'] = $records;
                    fg_save_themes($themes);
                    $message = 'Theme updated successfully.';
                }
            } elseif ($action === 'delete_theme') {
                $themeKey = (string) ($_POST['theme_key'] ?? '');
                if (!isset($records[$themeKey])) {
                    $errors[] = 'Unknown theme specified for deletion.';
                } elseif (count($records) <= 1) {
                    $errors[] = 'At least one theme must remain available.';
                } else {
                    $settingsData = fg_load_settings();
                    $currentDefault = $settingsData['settings']['default_theme']['value'] ?? ($themes['metadata']['default'] ?? '');
                    if ($currentDefault === $themeKey) {
                        $errors[] = 'Cannot delete the active default theme.';
                    } else {
                        unset($records[$themeKey]);
                        if (($themes['metadata']['default'] ?? '') === $themeKey) {
                            $themes['metadata']['default'] = array_key_first($records);
                        }
                        $themes['records'] = $records;
                        fg_save_themes($themes);
                        $message = 'Theme deleted successfully.';
                    }
                }
            } elseif ($action === 'set_default_theme') {
                $themeKey = (string) ($_POST['theme_key'] ?? '');
                if (!isset($records[$themeKey])) {
                    $errors[] = 'Theme not recognised when setting default.';
                } else {
                    $themes['metadata']['default'] = $themeKey;
                    fg_save_themes($themes);
                    $settingsData = fg_load_settings();
                    if (isset($settingsData['settings']['default_theme'])) {
                        $settingsData['settings']['default_theme']['value'] = $themeKey;
                        fg_save_settings($settingsData);
                    }
                    $message = 'Default theme updated.';
                }
            }
        } elseif (in_array($action, ['create_page', 'update_page', 'delete_page'], true)) {
            try {
                $pages = fg_load_pages();
                if (!isset($pages['records']) || !is_array($pages['records'])) {
                    $pages = fg_default_pages_dataset();
                    fg_save_pages($pages);
                }
            } catch (Throwable $exception) {
                $errors[] = 'Unable to load pages dataset: ' . $exception->getMessage();
                $pages = fg_default_pages_dataset();
            }

            if ($action === 'create_page') {
                $input = [
                    'title' => $_POST['title'] ?? '',
                    'slug' => $_POST['slug'] ?? '',
                    'summary' => $_POST['summary'] ?? '',
                    'content' => $_POST['content'] ?? '',
                    'format' => $_POST['format'] ?? 'html',
                    'visibility' => $_POST['visibility'] ?? 'public',
                    'allowed_roles' => $_POST['allowed_roles'] ?? [],
                    'show_in_navigation' => isset($_POST['show_in_navigation']),
                    'template' => $_POST['template'] ?? 'standard',
                    'variables' => [],
                    'owner_id' => $current['id'] ?? null,
                ];
                try {
                    fg_add_page($input);
                    $message = 'Page created successfully.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            } elseif ($action === 'update_page') {
                $pageId = (int) ($_POST['page_id'] ?? 0);
                if ($pageId <= 0) {
                    $errors[] = 'Invalid page identifier supplied.';
                } else {
                    $input = [
                        'title' => $_POST['title'] ?? '',
                        'slug' => $_POST['slug'] ?? '',
                        'summary' => $_POST['summary'] ?? '',
                        'content' => $_POST['content'] ?? '',
                        'format' => $_POST['format'] ?? 'html',
                        'visibility' => $_POST['visibility'] ?? 'public',
                        'allowed_roles' => $_POST['allowed_roles'] ?? [],
                        'show_in_navigation' => isset($_POST['show_in_navigation']),
                        'template' => $_POST['template'] ?? 'standard',
                        'variables' => [],
                    ];
                    try {
                        fg_update_page($pageId, $input);
                        $message = 'Page updated successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'delete_page') {
                $pageId = (int) ($_POST['page_id'] ?? 0);
                if ($pageId <= 0) {
                    $errors[] = 'Unknown page specified for deletion.';
                } else {
                    try {
                        fg_delete_page($pageId);
                        $message = 'Page deleted successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            }
        } elseif (in_array($action, ['create_content_module', 'update_content_module', 'delete_content_module', 'adopt_content_blueprint'], true)) {
            try {
                $contentModules = fg_load_content_modules();
                if (!isset($contentModules['records']) || !is_array($contentModules['records'])) {
                    $contentModules = fg_default_content_modules_dataset();
                    fg_save_content_modules($contentModules);
                }
            } catch (Throwable $exception) {
                $errors[] = 'Unable to load content module dataset: ' . $exception->getMessage();
                $contentModules = fg_default_content_modules_dataset();
            }

            if ($action === 'delete_content_module') {
                $moduleId = (int) ($_POST['module_id'] ?? 0);
                if ($moduleId <= 0) {
                    $errors[] = 'Unknown module selected for deletion.';
                } else {
                    try {
                        if (fg_delete_content_module($moduleId)) {
                            $message = 'Content module deleted successfully.';
                        } else {
                            $errors[] = 'Content module could not be deleted or no longer exists.';
                        }
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } else {
                $payload = [
                    'label' => $_POST['label'] ?? '',
                    'dataset' => $_POST['dataset'] ?? 'posts',
                    'format' => $_POST['format'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'categories' => $_POST['categories'] ?? [],
                    'fields' => $_POST['fields'] ?? [],
                    'profile_prompts' => $_POST['profile_prompts'] ?? [],
                    'wizard_steps' => $_POST['wizard_steps'] ?? [],
                    'css_tokens' => $_POST['css_tokens'] ?? [],
                ];

                if ($action === 'adopt_content_blueprint') {
                    $blueprintRaw = $_POST['blueprint'] ?? '';
                    $decoded = json_decode((string) $blueprintRaw, true);
                    if (is_array($decoded)) {
                        $payload['label'] = $decoded['label'] ?? ($decoded['title'] ?? 'Content module');
                        $payload['description'] = $decoded['description'] ?? ($decoded['summary'] ?? '');
                        $payload['format'] = $decoded['format'] ?? '';
                        if (!empty($decoded['categories']) && is_array($decoded['categories'])) {
                            $payload['categories'] = $decoded['categories'];
                        }
                        if (!empty($decoded['fields']) && is_array($decoded['fields'])) {
                            $payload['fields'] = array_map(static function (array $field): string {
                                $label = trim((string) ($field['label'] ?? ($field['title'] ?? '')));
                                $description = trim((string) ($field['description'] ?? ''));
                                if ($description === '') {
                                    return $label;
                                }
                                return $label . '|' . $description;
                            }, $decoded['fields']);
                        }
                        if (!empty($decoded['profile_prompts']) && is_array($decoded['profile_prompts'])) {
                            $payload['profile_prompts'] = array_map(static function (array $prompt): string {
                                $label = trim((string) ($prompt['label'] ?? ($prompt['name'] ?? '')));
                                $description = trim((string) ($prompt['description'] ?? ''));
                                if ($description === '') {
                                    return $label;
                                }
                                return $label . '|' . $description;
                            }, $decoded['profile_prompts']);
                        }
                        if (!empty($decoded['wizard_steps']) && is_array($decoded['wizard_steps'])) {
                            $payload['wizard_steps'] = array_map(static function (array $step): string {
                                $title = trim((string) ($step['title'] ?? ''));
                                $prompt = trim((string) ($step['prompt'] ?? ''));
                                if ($prompt === '') {
                                    return $title;
                                }
                                return $title . '|' . $prompt;
                            }, $decoded['wizard_steps']);
                        }
                        if (!empty($decoded['css_tokens']) && is_array($decoded['css_tokens'])) {
                            $payload['css_tokens'] = $decoded['css_tokens'];
                        }
                    }
                }

                if ($action === 'create_content_module' || $action === 'adopt_content_blueprint') {
                    try {
                        $created = fg_add_content_module($payload);
                        $message = 'Content module "' . ($created['label'] ?? 'Module') . '" created.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                } elseif ($action === 'update_content_module') {
                    $moduleId = (int) ($_POST['module_id'] ?? 0);
                    if ($moduleId <= 0) {
                        $errors[] = 'Invalid module identifier supplied.';
                    } else {
                        try {
                            $updated = fg_update_content_module($moduleId, $payload);
                            if ($updated !== null) {
                                $message = 'Content module updated successfully.';
                            } else {
                                $errors[] = 'Content module not found.';
                            }
                        } catch (Throwable $exception) {
                            $errors[] = $exception->getMessage();
                        }
                    }
                }
            }
            try {
                $contentModules = fg_load_content_modules();
            } catch (Throwable $exception) {
                $errors[] = 'Unable to refresh content module dataset: ' . $exception->getMessage();
            }
        } elseif (in_array($action, ['create_project_status', 'update_project_status', 'delete_project_status'], true)) {
            try {
                $projectStatus = fg_load_project_status();
                if (!isset($projectStatus['records']) || !is_array($projectStatus['records'])) {
                    $projectStatus = fg_default_project_status_dataset();
                }
            } catch (Throwable $exception) {
                $errors[] = 'Unable to load project status dataset: ' . $exception->getMessage();
                $projectStatus = fg_default_project_status_dataset();
            }

            if ($action === 'create_project_status') {
                $linksInput = $_POST['links'] ?? '';
                $links = [];
                if (is_array($linksInput)) {
                    $links = $linksInput;
                } else {
                    $parts = preg_split('/\r?\n/', (string) $linksInput);
                    foreach ($parts as $part) {
                        $trimmed = trim($part);
                        if ($trimmed !== '') {
                            $links[] = $trimmed;
                        }
                    }
                }

                try {
                    fg_add_project_status([
                        'title' => $_POST['title'] ?? '',
                        'summary' => $_POST['summary'] ?? '',
                        'status' => $_POST['status'] ?? 'planned',
                        'category' => $_POST['category'] ?? '',
                        'owner_role' => $_POST['owner_role'] ?? '',
                        'owner_user_id' => $_POST['owner_user_id'] ?? null,
                        'milestone' => $_POST['milestone'] ?? '',
                        'progress' => $_POST['progress'] ?? 0,
                        'links' => $links,
                    ]);
                    $message = 'Roadmap entry created successfully.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            } elseif ($action === 'update_project_status') {
                $statusId = (int) ($_POST['project_status_id'] ?? 0);
                $linksInput = $_POST['links'] ?? '';
                $links = [];
                if (is_array($linksInput)) {
                    $links = $linksInput;
                } else {
                    $parts = preg_split('/\r?\n/', (string) $linksInput);
                    foreach ($parts as $part) {
                        $trimmed = trim($part);
                        if ($trimmed !== '') {
                            $links[] = $trimmed;
                        }
                    }
                }

                try {
                    fg_update_project_status($statusId, [
                        'title' => $_POST['title'] ?? '',
                        'summary' => $_POST['summary'] ?? '',
                        'status' => $_POST['status'] ?? 'planned',
                        'category' => $_POST['category'] ?? '',
                        'owner_role' => $_POST['owner_role'] ?? '',
                        'owner_user_id' => $_POST['owner_user_id'] ?? null,
                        'milestone' => $_POST['milestone'] ?? '',
                        'progress' => $_POST['progress'] ?? 0,
                        'links' => $links,
                    ]);
                    $message = 'Roadmap entry updated successfully.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            } elseif ($action === 'delete_project_status') {
                $statusId = (int) ($_POST['project_status_id'] ?? 0);
                try {
                    fg_delete_project_status($statusId);
                    $message = 'Roadmap entry deleted.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            }

            $projectStatus = fg_load_project_status();
        } elseif (in_array($action, ['create_changelog_entry', 'update_changelog_entry', 'delete_changelog_entry'], true)) {
            try {
                $changelog = fg_load_changelog();
                if (!isset($changelog['records']) || !is_array($changelog['records'])) {
                    $changelog = fg_default_changelog_dataset();
                }
            } catch (Throwable $exception) {
                $errors[] = 'Unable to load changelog dataset: ' . $exception->getMessage();
                $changelog = fg_default_changelog_dataset();
            }

            if ($action === 'create_changelog_entry') {
                try {
                    fg_add_changelog_entry([
                        'title' => $_POST['title'] ?? '',
                        'summary' => $_POST['summary'] ?? '',
                        'type' => $_POST['type'] ?? 'announcement',
                        'visibility' => $_POST['visibility'] ?? 'public',
                        'highlight' => isset($_POST['highlight']),
                        'body' => $_POST['body'] ?? '',
                        'tags' => $_POST['tags'] ?? '',
                        'links' => $_POST['links'] ?? '',
                        'related_project_status_ids' => $_POST['related_project_status_ids'] ?? '',
                        'published_at' => $_POST['published_at'] ?? '',
                    ]);
                    $message = 'Changelog entry created successfully.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            } elseif ($action === 'update_changelog_entry') {
                $entryId = (int) ($_POST['changelog_id'] ?? 0);
                if ($entryId <= 0) {
                    $errors[] = 'Invalid changelog entry specified for update.';
                } else {
                    try {
                        fg_update_changelog_entry($entryId, [
                            'title' => $_POST['title'] ?? '',
                            'summary' => $_POST['summary'] ?? '',
                            'type' => $_POST['type'] ?? 'announcement',
                            'visibility' => $_POST['visibility'] ?? 'public',
                            'highlight' => isset($_POST['highlight']),
                            'body' => $_POST['body'] ?? '',
                            'tags' => $_POST['tags'] ?? '',
                            'links' => $_POST['links'] ?? '',
                            'related_project_status_ids' => $_POST['related_project_status_ids'] ?? '',
                            'published_at' => $_POST['published_at'] ?? '',
                        ]);
                        $message = 'Changelog entry updated successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'delete_changelog_entry') {
                $entryId = (int) ($_POST['changelog_id'] ?? 0);
                if ($entryId <= 0) {
                    $errors[] = 'Invalid changelog entry specified for deletion.';
                } else {
                    try {
                        fg_delete_changelog_entry($entryId);
                        $message = 'Changelog entry deleted successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            }

            $changelog = fg_load_changelog();
        } elseif (in_array($action, ['create_feature_request', 'update_feature_request', 'delete_feature_request'], true)) {
            try {
                $featureRequests = fg_load_feature_requests();
                if (!isset($featureRequests['records']) || !is_array($featureRequests['records'])) {
                    $featureRequests = fg_default_feature_requests_dataset();
                }
            } catch (Throwable $exception) {
                $errors[] = 'Unable to load feature request dataset: ' . $exception->getMessage();
                $featureRequests = fg_default_feature_requests_dataset();
            }

            if ($action === 'create_feature_request') {
                try {
                    fg_add_feature_request([
                        'title' => $_POST['title'] ?? '',
                        'summary' => $_POST['summary'] ?? '',
                        'details' => $_POST['details'] ?? '',
                        'status' => $_POST['status'] ?? ($featureRequestStatusOptions[0] ?? 'open'),
                        'priority' => $_POST['priority'] ?? ($featureRequestPriorityOptions[0] ?? 'medium'),
                        'visibility' => $_POST['visibility'] ?? $featureRequestDefaultVisibility,
                        'impact' => $_POST['impact'] ?? 3,
                        'effort' => $_POST['effort'] ?? 3,
                        'tags' => $_POST['tags'] ?? '',
                        'reference_links' => $_POST['reference_links'] ?? '',
                        'supporters' => $_POST['supporters'] ?? '',
                        'requestor_user_id' => $_POST['requestor_user_id'] ?? null,
                        'owner_role' => $_POST['owner_role'] ?? '',
                        'owner_user_id' => $_POST['owner_user_id'] ?? null,
                        'admin_notes' => $_POST['admin_notes'] ?? '',
                        'performed_by' => $current['id'] ?? null,
                        'trigger' => 'setup_ui',
                    ]);
                    $message = 'Feature request created successfully.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            } elseif ($action === 'update_feature_request') {
                $requestId = (int) ($_POST['feature_request_id'] ?? 0);
                if ($requestId <= 0) {
                    $errors[] = 'Unknown feature request specified for update.';
                } else {
                    try {
                        fg_update_feature_request($requestId, [
                            'title' => $_POST['title'] ?? '',
                            'summary' => $_POST['summary'] ?? '',
                            'details' => $_POST['details'] ?? '',
                            'status' => $_POST['status'] ?? ($featureRequestStatusOptions[0] ?? 'open'),
                            'priority' => $_POST['priority'] ?? ($featureRequestPriorityOptions[0] ?? 'medium'),
                            'visibility' => $_POST['visibility'] ?? $featureRequestDefaultVisibility,
                            'impact' => $_POST['impact'] ?? 3,
                            'effort' => $_POST['effort'] ?? 3,
                            'tags' => $_POST['tags'] ?? '',
                            'reference_links' => $_POST['reference_links'] ?? '',
                            'supporters' => $_POST['supporters'] ?? '',
                            'requestor_user_id' => $_POST['requestor_user_id'] ?? null,
                            'owner_role' => $_POST['owner_role'] ?? '',
                            'owner_user_id' => $_POST['owner_user_id'] ?? null,
                            'admin_notes' => $_POST['admin_notes'] ?? '',
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_ui',
                        ]);
                        $message = 'Feature request updated successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'delete_feature_request') {
                $requestId = (int) ($_POST['feature_request_id'] ?? 0);
                if ($requestId <= 0) {
                    $errors[] = 'Unknown feature request specified for deletion.';
                } else {
                    try {
                        fg_delete_feature_request($requestId, [
                            'trigger' => 'setup_ui',
                            'performed_by' => $current['id'] ?? null,
                        ]);
                        $message = 'Feature request deleted successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            }

            $featureRequests = fg_load_feature_requests();
        } elseif (in_array($action, ['create_bug_report', 'update_bug_report', 'delete_bug_report'], true)) {
            try {
                $bugReports = fg_load_bug_reports();
                if (!isset($bugReports['records']) || !is_array($bugReports['records'])) {
                    $bugReports = fg_default_bug_reports_dataset();
                }
            } catch (Throwable $exception) {
                $errors[] = 'Unable to load bug report dataset: ' . $exception->getMessage();
                $bugReports = fg_default_bug_reports_dataset();
            }

            if ($action === 'create_bug_report') {
                try {
                    fg_add_bug_report([
                        'title' => $_POST['title'] ?? '',
                        'summary' => $_POST['summary'] ?? '',
                        'details' => $_POST['details'] ?? '',
                        'status' => $_POST['status'] ?? ($bugStatusOptions[0] ?? 'new'),
                        'severity' => $_POST['severity'] ?? ($bugSeverityOptions[0] ?? 'medium'),
                        'visibility' => $_POST['visibility'] ?? $bugDefaultVisibility,
                        'reporter_user_id' => $_POST['reporter_user_id'] ?? null,
                        'owner_role' => $_POST['owner_role'] ?? $bugDefaultOwnerRole,
                        'owner_user_id' => $_POST['owner_user_id'] ?? null,
                        'tags' => $_POST['tags'] ?? '',
                        'steps_to_reproduce' => $_POST['steps_to_reproduce'] ?? '',
                        'affected_versions' => $_POST['affected_versions'] ?? '',
                        'environment' => $_POST['environment'] ?? '',
                        'reference_links' => $_POST['reference_links'] ?? '',
                        'attachments' => $_POST['attachments'] ?? '',
                        'resolution_notes' => $_POST['resolution_notes'] ?? '',
                        'watchers' => $_POST['watchers'] ?? '',
                        'performed_by' => $current['id'] ?? null,
                        'trigger' => 'setup_ui',
                    ]);
                    $message = 'Bug report created successfully.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            } elseif ($action === 'update_bug_report') {
                $bugId = (int) ($_POST['bug_report_id'] ?? 0);
                if ($bugId <= 0) {
                    $errors[] = 'Unknown bug report specified for update.';
                } else {
                    try {
                        fg_update_bug_report($bugId, [
                            'title' => $_POST['title'] ?? '',
                            'summary' => $_POST['summary'] ?? '',
                            'details' => $_POST['details'] ?? '',
                            'status' => $_POST['status'] ?? ($bugStatusOptions[0] ?? 'new'),
                            'severity' => $_POST['severity'] ?? ($bugSeverityOptions[0] ?? 'medium'),
                            'visibility' => $_POST['visibility'] ?? $bugDefaultVisibility,
                            'reporter_user_id' => $_POST['reporter_user_id'] ?? null,
                            'owner_role' => $_POST['owner_role'] ?? $bugDefaultOwnerRole,
                            'owner_user_id' => $_POST['owner_user_id'] ?? null,
                            'tags' => $_POST['tags'] ?? '',
                            'steps_to_reproduce' => $_POST['steps_to_reproduce'] ?? '',
                            'affected_versions' => $_POST['affected_versions'] ?? '',
                            'environment' => $_POST['environment'] ?? '',
                            'reference_links' => $_POST['reference_links'] ?? '',
                            'attachments' => $_POST['attachments'] ?? '',
                            'resolution_notes' => $_POST['resolution_notes'] ?? '',
                            'watchers' => $_POST['watchers'] ?? '',
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_ui',
                            'touch_activity' => true,
                        ]);
                        $message = 'Bug report updated successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'delete_bug_report') {
                $bugId = (int) ($_POST['bug_report_id'] ?? 0);
                if ($bugId <= 0) {
                    $errors[] = 'Unknown bug report specified for deletion.';
                } else {
                    try {
                        fg_delete_bug_report($bugId, [
                            'trigger' => 'setup_ui',
                            'performed_by' => $current['id'] ?? null,
                        ]);
                        $message = 'Bug report deleted successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            }

            $bugReports = fg_load_bug_reports();
        } elseif (in_array($action, ['create_poll', 'update_poll', 'delete_poll'], true)) {
            try {
                $polls = fg_load_polls();
                if (!isset($polls['records']) || !is_array($polls['records'])) {
                    $polls = fg_default_polls_dataset();
                }
            } catch (Throwable $exception) {
                $errors[] = 'Unable to load poll dataset: ' . $exception->getMessage();
                $polls = fg_default_polls_dataset();
            }

            if ($action === 'create_poll') {
                try {
                    fg_add_poll([
                        'question' => $_POST['question'] ?? '',
                        'description' => $_POST['description'] ?? '',
                        'status' => $_POST['status'] ?? ($pollStatusOptions[0] ?? 'draft'),
                        'visibility' => $_POST['visibility'] ?? $pollDefaultVisibility,
                        'allow_multiple' => isset($_POST['allow_multiple']),
                        'max_selections' => $_POST['max_selections'] ?? ($pollAllowMultipleDefault ? 0 : 1),
                        'options' => $_POST['options'] ?? '',
                        'expires_at' => $_POST['expires_at'] ?? '',
                        'owner_role' => $_POST['owner_role'] ?? '',
                        'owner_user_id' => $_POST['owner_user_id'] ?? null,
                        'performed_by' => $current['id'] ?? null,
                        'trigger' => 'setup_ui',
                    ]);
                    $message = 'Poll created successfully.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            } elseif ($action === 'update_poll') {
                $pollId = (int) ($_POST['poll_id'] ?? 0);
                if ($pollId <= 0) {
                    $errors[] = 'Unknown poll specified for update.';
                } else {
                    try {
                        fg_update_poll($pollId, [
                            'question' => $_POST['question'] ?? '',
                            'description' => $_POST['description'] ?? '',
                            'status' => $_POST['status'] ?? ($pollStatusOptions[0] ?? 'draft'),
                            'visibility' => $_POST['visibility'] ?? $pollDefaultVisibility,
                            'allow_multiple' => isset($_POST['allow_multiple']),
                            'max_selections' => $_POST['max_selections'] ?? '',
                            'options' => $_POST['options'] ?? '',
                            'expires_at' => $_POST['expires_at'] ?? '',
                            'owner_role' => $_POST['owner_role'] ?? '',
                            'owner_user_id' => $_POST['owner_user_id'] ?? null,
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_ui',
                        ]);
                        $message = 'Poll updated successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'delete_poll') {
                $pollId = (int) ($_POST['poll_id'] ?? 0);
                if ($pollId <= 0) {
                    $errors[] = 'Unknown poll specified for deletion.';
                } else {
                    try {
                        fg_delete_poll($pollId, [
                            'trigger' => 'setup_ui',
                            'performed_by' => $current['id'] ?? null,
                        ]);
                        $message = 'Poll deleted successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
        }

        $polls = fg_load_polls();
    } elseif (in_array($action, ['create_event', 'update_event', 'delete_event'], true)) {
        try {
            $events = fg_load_events();
            if (!isset($events['records']) || !is_array($events['records'])) {
                $events = fg_default_events_dataset();
            }
        } catch (Throwable $exception) {
            $errors[] = 'Unable to load event dataset: ' . $exception->getMessage();
            $events = fg_default_events_dataset();
        }

        if ($action === 'create_event') {
            try {
                fg_add_event([
                    'title' => $_POST['title'] ?? '',
                    'summary' => $_POST['summary'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'status' => $_POST['status'] ?? ($eventStatusOptions[0] ?? 'draft'),
                    'visibility' => $_POST['visibility'] ?? $eventDefaultVisibility,
                    'start_at' => $_POST['start_at'] ?? '',
                    'end_at' => $_POST['end_at'] ?? '',
                    'timezone' => $_POST['timezone'] ?? $eventDefaultTimezone,
                    'location' => $_POST['location'] ?? '',
                    'location_url' => $_POST['location_url'] ?? '',
                    'allow_rsvp' => isset($_POST['allow_rsvp']),
                    'rsvp_policy' => $_POST['rsvp_policy'] ?? $eventRsvpPolicy,
                    'rsvp_limit' => $_POST['rsvp_limit'] ?? null,
                    'hosts' => $_POST['hosts'] ?? [],
                    'collaborators' => $_POST['collaborators'] ?? [],
                    'tags' => $_POST['tags'] ?? [],
                    'attachments' => $_POST['attachments'] ?? [],
                    'performed_by' => $current['id'] ?? null,
                    'trigger' => 'setup_ui',
                ]);
                $message = 'Event created successfully.';
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        } elseif ($action === 'update_event') {
            $eventId = (int) ($_POST['event_id'] ?? 0);
            if ($eventId <= 0) {
                $errors[] = 'Unknown event specified for update.';
            } else {
                try {
                    fg_update_event($eventId, [
                        'title' => $_POST['title'] ?? '',
                        'summary' => $_POST['summary'] ?? '',
                        'description' => $_POST['description'] ?? '',
                        'status' => $_POST['status'] ?? ($eventStatusOptions[0] ?? 'draft'),
                        'visibility' => $_POST['visibility'] ?? $eventDefaultVisibility,
                        'start_at' => $_POST['start_at'] ?? '',
                        'end_at' => $_POST['end_at'] ?? '',
                        'timezone' => $_POST['timezone'] ?? $eventDefaultTimezone,
                        'location' => $_POST['location'] ?? '',
                        'location_url' => $_POST['location_url'] ?? '',
                        'allow_rsvp' => isset($_POST['allow_rsvp']),
                        'rsvp_policy' => $_POST['rsvp_policy'] ?? $eventRsvpPolicy,
                        'rsvp_limit' => $_POST['rsvp_limit'] ?? null,
                        'hosts' => $_POST['hosts'] ?? [],
                        'collaborators' => $_POST['collaborators'] ?? [],
                        'tags' => $_POST['tags'] ?? [],
                        'attachments' => $_POST['attachments'] ?? [],
                        'performed_by' => $current['id'] ?? null,
                        'trigger' => 'setup_ui',
                    ]);
                    $message = 'Event updated successfully.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        } elseif ($action === 'delete_event') {
            $eventId = (int) ($_POST['event_id'] ?? 0);
            if ($eventId <= 0) {
                $errors[] = 'Unknown event specified for deletion.';
            } else {
                try {
                    fg_delete_event($eventId, [
                        'trigger' => 'setup_ui',
                        'performed_by' => $current['id'] ?? null,
                    ]);
                    $message = 'Event deleted successfully.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        }

        $events = fg_load_events();
    } elseif (in_array($action, ['create_knowledge_article', 'update_knowledge_article', 'delete_knowledge_article'], true)) {
            try {
                $knowledgeBase = fg_load_knowledge_base();
                if (!isset($knowledgeBase['records']) || !is_array($knowledgeBase['records'])) {
                    $knowledgeBase = fg_default_knowledge_base_dataset();
                }
            } catch (Throwable $exception) {
                $errors[] = 'Unable to load knowledge base dataset: ' . $exception->getMessage();
                $knowledgeBase = fg_default_knowledge_base_dataset();
            }

            if ($action === 'create_knowledge_article') {
                try {
                    fg_add_knowledge_article([
                        'title' => $_POST['title'] ?? '',
                        'summary' => $_POST['summary'] ?? '',
                        'content' => $_POST['content'] ?? '',
                        'slug' => $_POST['slug'] ?? '',
                        'status' => $_POST['status'] ?? $knowledgeDefaultStatus,
                        'visibility' => $_POST['visibility'] ?? $knowledgeDefaultVisibility,
                        'template' => $_POST['template'] ?? 'article',
                        'tags' => $_POST['tags'] ?? '',
                        'attachments' => $_POST['attachments'] ?? '',
                        'author_user_id' => $_POST['author_user_id'] ?? null,
                        'category_id' => $_POST['category_id'] ?? null,
                    ], [
                        'performed_by' => $current['id'] ?? null,
                        'trigger' => 'setup_ui',
                        'default_category_id' => $knowledgeDefaultCategoryId,
                    ]);
                    $message = 'Knowledge base article created successfully.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            } elseif ($action === 'update_knowledge_article') {
                $articleId = (int) ($_POST['knowledge_article_id'] ?? 0);
                if ($articleId <= 0) {
                    $errors[] = 'Unknown knowledge base article specified for update.';
                } else {
                    try {
                        fg_update_knowledge_article($articleId, [
                            'title' => $_POST['title'] ?? '',
                            'summary' => $_POST['summary'] ?? '',
                            'content' => $_POST['content'] ?? '',
                            'slug' => $_POST['slug'] ?? '',
                            'status' => $_POST['status'] ?? $knowledgeDefaultStatus,
                            'visibility' => $_POST['visibility'] ?? $knowledgeDefaultVisibility,
                            'template' => $_POST['template'] ?? 'article',
                            'tags' => $_POST['tags'] ?? '',
                            'attachments' => $_POST['attachments'] ?? '',
                            'author_user_id' => $_POST['author_user_id'] ?? null,
                            'category_id' => $_POST['category_id'] ?? null,
                        ], [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_ui',
                        ]);
                        $message = 'Knowledge base article updated successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'delete_knowledge_article') {
                $articleId = (int) ($_POST['knowledge_article_id'] ?? 0);
                if ($articleId <= 0) {
                    $errors[] = 'Unknown knowledge base article specified for deletion.';
                } else {
                    try {
                        if (!fg_delete_knowledge_article($articleId, [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_ui',
                        ])) {
                            $errors[] = 'The requested knowledge base entry could not be removed.';
                        } else {
                            $message = 'Knowledge base article deleted successfully.';
                        }
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            }

            $knowledgeBase = fg_load_knowledge_base();
        } elseif (in_array($action, ['create_automation', 'update_automation', 'delete_automation'], true)) {
            try {
                $automations = fg_load_automations();
                if (!isset($automations['records']) || !is_array($automations['records'])) {
                    $automations = fg_default_automations_dataset();
                }
            } catch (Throwable $exception) {
                $errors[] = 'Unable to load automation dataset: ' . $exception->getMessage();
                $automations = fg_default_automations_dataset();
            }

            if ($action === 'create_automation') {
                try {
                    fg_add_automation([
                        'name' => $_POST['name'] ?? '',
                        'description' => $_POST['description'] ?? '',
                        'status' => $_POST['status'] ?? $automationDefaultStatus,
                        'trigger' => $_POST['trigger'] ?? ($automationTriggerOptions[0] ?? 'user_registered'),
                        'conditions' => $_POST['conditions'] ?? '',
                        'actions' => $_POST['actions'] ?? '',
                        'owner_role' => $_POST['owner_role'] ?? $automationDefaultOwnerRole,
                        'owner_user_id' => $_POST['owner_user_id'] ?? null,
                        'run_limit' => $_POST['run_limit'] ?? null,
                        'priority' => $_POST['priority'] ?? ($automationPriorityOptions[0] ?? 'medium'),
                        'tags' => $_POST['tags'] ?? '',
                    ], [
                        'performed_by' => $current['id'] ?? null,
                        'trigger' => 'setup_ui',
                    ]);
                    $message = 'Automation created successfully.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            } elseif ($action === 'update_automation') {
                $automationId = (int) ($_POST['automation_id'] ?? 0);
                if ($automationId <= 0) {
                    $errors[] = 'Unknown automation specified for update.';
                } else {
                    try {
                        fg_update_automation($automationId, [
                            'name' => $_POST['name'] ?? '',
                            'description' => $_POST['description'] ?? '',
                            'status' => $_POST['status'] ?? $automationDefaultStatus,
                            'trigger' => $_POST['trigger'] ?? ($automationTriggerOptions[0] ?? 'user_registered'),
                            'conditions' => $_POST['conditions'] ?? '',
                            'actions' => $_POST['actions'] ?? '',
                            'owner_role' => $_POST['owner_role'] ?? $automationDefaultOwnerRole,
                            'owner_user_id' => $_POST['owner_user_id'] ?? null,
                            'run_limit' => $_POST['run_limit'] ?? null,
                            'run_count' => $_POST['run_count'] ?? null,
                            'last_run_at' => $_POST['last_run_at'] ?? null,
                            'priority' => $_POST['priority'] ?? ($automationPriorityOptions[0] ?? 'medium'),
                            'tags' => $_POST['tags'] ?? '',
                        ], [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_ui',
                        ]);
                        $message = 'Automation updated successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'delete_automation') {
                $automationId = (int) ($_POST['automation_id'] ?? 0);
                if ($automationId <= 0) {
                    $errors[] = 'Unknown automation specified for deletion.';
                } else {
                    try {
                        fg_delete_automation($automationId, [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_ui',
                        ]);
                        $message = 'Automation deleted successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            }

            $automations = fg_load_automations();
        } elseif (in_array($action, ['create_knowledge_category', 'update_knowledge_category', 'delete_knowledge_category'], true)) {
            try {
                $knowledgeCategories = fg_load_knowledge_categories();
                if (!isset($knowledgeCategories['records']) || !is_array($knowledgeCategories['records'])) {
                    $knowledgeCategories = fg_default_knowledge_categories_dataset();
                }
            } catch (Throwable $exception) {
                $errors[] = 'Unable to load knowledge category dataset: ' . $exception->getMessage();
                $knowledgeCategories = fg_default_knowledge_categories_dataset();
            }

            if ($action === 'create_knowledge_category') {
                try {
                    fg_add_knowledge_category([
                        'name' => $_POST['name'] ?? '',
                        'slug' => $_POST['slug'] ?? '',
                        'description' => $_POST['description'] ?? '',
                        'visibility' => $_POST['visibility'] ?? 'public',
                        'ordering' => $_POST['ordering'] ?? 0,
                    ], [
                        'performed_by' => $current['id'] ?? null,
                        'trigger' => 'setup_ui',
                    ]);
                    $message = 'Knowledge base category created successfully.';
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            } elseif ($action === 'update_knowledge_category') {
                $categoryId = (int) ($_POST['knowledge_category_id'] ?? 0);
                if ($categoryId <= 0) {
                    $errors[] = 'Unknown knowledge base category specified for update.';
                } else {
                    try {
                        fg_update_knowledge_category($categoryId, [
                            'name' => $_POST['name'] ?? '',
                            'slug' => $_POST['slug'] ?? '',
                            'description' => $_POST['description'] ?? '',
                            'visibility' => $_POST['visibility'] ?? 'public',
                            'ordering' => $_POST['ordering'] ?? 0,
                        ], [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_ui',
                        ]);
                        $message = 'Knowledge base category updated successfully.';
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } elseif ($action === 'delete_knowledge_category') {
                $categoryId = (int) ($_POST['knowledge_category_id'] ?? 0);
                if ($categoryId <= 0) {
                    $errors[] = 'Unknown knowledge base category specified for deletion.';
                } else {
                    try {
                        if (!fg_delete_knowledge_category($categoryId, [
                            'performed_by' => $current['id'] ?? null,
                            'trigger' => 'setup_ui',
                        ])) {
                            $errors[] = 'The requested category could not be removed.';
                        } else {
                            $message = 'Knowledge base category deleted successfully.';
                        }
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            }

            $knowledgeCategories = fg_load_knowledge_categories();
        } else {
            $asset = $_POST['asset'] ?? '';
            $configurations = fg_load_asset_configurations();
            $definition = $configurations['records'][$asset]['parameters'] ?? [];
            $roles = fg_load_settings()['role_definitions'] ?? [];

            if ($asset === '' || !isset($configurations['records'][$asset])) {
                $errors[] = 'Unknown asset supplied.';
            } else {
                if ($action === 'update_defaults') {
                    $defaults = $_POST['defaults'] ?? [];
                    $normalized = [];
                    foreach ($definition as $key => $parameter) {
                        $type = $parameter['type'] ?? 'text';
                        if ($type === 'boolean') {
                            $normalized[$key] = isset($defaults[$key]);
                        } elseif ($type === 'select') {
                            $options = $parameter['options'] ?? [];
                            $value = $defaults[$key] ?? ($parameter['default'] ?? '');
                            $optionStrings = array_map('strval', $options);
                            if (!in_array((string) $value, $optionStrings, true)) {
                                $value = $parameter['default'] ?? '';
                            }
                            $normalized[$key] = $value;
                        } else {
                            $normalized[$key] = trim((string) ($defaults[$key] ?? ''));
                        }
                    }
                    fg_update_asset_configuration($asset, $normalized);

                    $allowedRoles = $_POST['allowed_roles'] ?? [];
                    if (!is_array($allowedRoles)) {
                        $allowedRoles = [];
                    }
                    $allowedRoles = array_values(array_intersect(array_keys($roles), array_map('strval', $allowedRoles)));
                    $allowUserOverride = isset($_POST['allow_user_override']);
                    fg_update_asset_permissions($asset, $allowedRoles, $allowUserOverride);
                    $message = 'Defaults saved successfully.';
                } elseif ($action === 'update_override') {
                    $scope = $_POST['scope'] ?? 'global';
                    $identifier = $_POST['identifier'] ?? '';
                    $values = $_POST['override'] ?? [];
                    $normalized = [];
                    foreach ($definition as $key => $parameter) {
                        $type = $parameter['type'] ?? 'text';
                        if ($type === 'boolean') {
                            $normalized[$key] = isset($values[$key]);
                        } elseif ($type === 'select') {
                            $options = $parameter['options'] ?? [];
                            $value = $values[$key] ?? ($parameter['default'] ?? '');
                            $optionStrings = array_map('strval', $options);
                            if (!in_array((string) $value, $optionStrings, true)) {
                                $value = $parameter['default'] ?? '';
                            }
                            $normalized[$key] = $value;
                        } else {
                            $normalized[$key] = trim((string) ($values[$key] ?? ''));
                        }
                    }

                    if ($scope === 'roles') {
                        if (!isset($roles[$identifier])) {
                            $errors[] = 'Unknown role for override.';
                        } else {
                            fg_update_asset_override($asset, 'roles', $identifier, $normalized);
                            $message = 'Role override saved.';
                        }
                    } elseif ($scope === 'users') {
                        $identifier = (string) $identifier;
                        if ($identifier === '') {
                            $errors[] = 'User selection required for overrides.';
                        } else {
                            fg_update_asset_override($asset, 'users', $identifier, $normalized);
                            $message = 'User override saved.';
                        }
                    } else {
                        fg_update_asset_override($asset, 'global', 'global', $normalized);
                        $message = 'Global override saved.';
                    }
                } elseif ($action === 'clear_override') {
                    $scope = $_POST['scope'] ?? 'global';
                    $identifier = $_POST['identifier'] ?? '';
                    if ($scope === 'roles' && $identifier === '') {
                        $errors[] = 'Role identifier missing for clearing override.';
                    } elseif ($scope === 'users' && $identifier === '') {
                        $errors[] = 'User identifier missing for clearing override.';
                    } else {
                        fg_clear_asset_override($asset, $scope, $identifier);
                        $message = 'Override removed.';
                    }
                }
            }
        }
    }

    $configurations = fg_load_asset_configurations();
    $overrides = fg_load_asset_overrides();
    $settings = fg_load_settings();
    $roles = $settings['role_definitions'] ?? [];
    $users = fg_load_users()['records'] ?? [];
    $themesData = fg_load_themes();
    $themeTokens = fg_load_theme_tokens();
    $defaultThemeSetting = $settings['settings']['default_theme']['value'] ?? ($themesData['metadata']['default'] ?? '');
    $themePolicy = $settings['settings']['theme_personalisation_policy']['value'] ?? 'enabled';
    $snapshotStore = fg_load_asset_snapshots();
    $snapshotMetadata = $snapshotStore['metadata'] ?? [];

    $datasets = [];
    foreach ($manifest as $name => $definition) {
        $format = fg_dataset_format($name);
        $nature = fg_dataset_nature($name);
        $path = fg_dataset_path($name);
        $exists = file_exists($path);
        $raw = '';
        if ($exists) {
            try {
                $raw = fg_load_dataset_contents($name);
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        $displayPayload = trim($raw);
        if ($displayPayload === '' && $format === 'json') {
            $defaultPreview = fg_dataset_default_payload($name);
            if ($defaultPreview !== null) {
                $displayPayload = trim($defaultPreview);
            }
        } elseif ($format === 'json' && $displayPayload !== '') {
            $decoded = json_decode($displayPayload, true);
            if (is_array($decoded)) {
                $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                if ($encoded !== false) {
                    $displayPayload = $encoded;
                }
            }
        }

        $lineCount = max(6, min(40, substr_count($displayPayload, "\n") + 1));
        $size = $exists ? fg_format_file_size((int) filesize($path)) : '0 B';
        $modified = $exists ? date('Y-m-d H:i', (int) filemtime($path)) : 'Not generated yet';
        $editable = $exists ? is_writable($path) : is_writable(dirname($path));

        $datasets[$name] = [
            'name' => $name,
            'label' => $definition['label'] ?? $name,
            'description' => $definition['description'] ?? '',
            'nature' => $nature,
            'format' => $format,
            'size' => $size,
            'modified' => $modified,
            'payload' => $displayPayload,
            'rows' => $lineCount,
            'editable' => $editable,
            'missing' => !$exists,
            'has_defaults' => fg_dataset_default_payload($name) !== null,
            'snapshots' => array_map(
                static function (array $snapshot) use ($users) {
                    $createdBy = $snapshot['context']['created_by'] ?? null;
                    $userLabel = '';
                    if ($createdBy !== null) {
                        foreach ($users as $user) {
                            if ((int) ($user['id'] ?? 0) === (int) $createdBy) {
                                $userLabel = $user['username'] ?? ($user['email'] ?? '');
                                break;
                            }
                        }
                    }

                    $preview = trim((string) ($snapshot['payload'] ?? ''));
                    if (strlen($preview) > 600) {
                        $preview = substr($preview, 0, 600) . "\n...";
                    }

                    return [
                        'id' => (int) ($snapshot['id'] ?? 0),
                        'reason' => $snapshot['reason'] ?? '',
                        'created_at' => $snapshot['created_at'] ?? '',
                        'created_by' => $userLabel,
                        'preview' => $preview,
                        'format' => $snapshot['format'] ?? 'json',
                        'context' => $snapshot['context'] ?? [],
                    ];
                },
                fg_list_dataset_snapshots($name, 5)
            ),
            'snapshot_limit' => (int) ($snapshotMetadata['per_dataset_limit'] ?? 0),
        ];
    }

    $activityLog = fg_load_activity_log();
    $activityRecordsRaw = $activityLog['records'] ?? [];

    $datasetLabels = [];
    foreach ($manifest as $name => $definition) {
        $datasetLabels[$name] = $definition['label'] ?? $name;
    }

    $activityFilters = [
        'dataset' => trim((string) ($_GET['log_dataset'] ?? '')),
        'category' => trim((string) ($_GET['log_category'] ?? '')),
        'action' => trim((string) ($_GET['log_action'] ?? '')),
        'user' => trim((string) ($_GET['log_user'] ?? '')),
    ];

    $activityLimit = (int) ($_GET['log_limit'] ?? 50);
    if ($activityLimit < 5) {
        $activityLimit = 5;
    }
    if ($activityLimit > 200) {
        $activityLimit = 200;
    }

    $activityCategories = [];
    $activityActions = [];
    foreach ($activityRecordsRaw as $entry) {
        $categoryName = (string) ($entry['category'] ?? '');
        if ($categoryName !== '') {
            $activityCategories[$categoryName] = true;
        }
        $actionName = (string) ($entry['action'] ?? '');
        if ($actionName !== '') {
            $activityActions[$actionName] = true;
        }
    }
    ksort($activityCategories);
    ksort($activityActions);

    $activityRecords = [];
    foreach ($activityRecordsRaw as $entry) {
        $categoryName = (string) ($entry['category'] ?? '');
        if ($activityFilters['category'] !== '' && $activityFilters['category'] !== $categoryName) {
            continue;
        }

        $actionName = (string) ($entry['action'] ?? '');
        if ($activityFilters['action'] !== '' && $activityFilters['action'] !== $actionName) {
            continue;
        }

        $datasetName = (string) ($entry['dataset'] ?? ($entry['details']['dataset'] ?? ''));
        if ($activityFilters['dataset'] !== '' && $activityFilters['dataset'] !== $datasetName) {
            continue;
        }

        $performedBy = $entry['performed_by'] ?? null;
        if ($activityFilters['user'] !== '') {
            $needle = strtolower($activityFilters['user']);
            $match = false;
            if (is_array($performedBy)) {
                $candidateFields = [
                    (string) ($performedBy['username'] ?? ''),
                    (string) ($performedBy['email'] ?? ''),
                    (string) ($performedBy['role'] ?? ''),
                    (string) ($performedBy['id'] ?? ''),
                ];
                foreach ($candidateFields as $field) {
                    if ($field !== '' && strpos(strtolower($field), $needle) !== false) {
                        $match = true;
                        break;
                    }
                }
            }

            if (!$match) {
                continue;
            }
        }

        $createdAt = (string) ($entry['created_at'] ?? '');
        $createdAtDisplay = '';
        if ($createdAt !== '') {
            $timestamp = strtotime($createdAt);
            if ($timestamp !== false) {
                $createdAtDisplay = gmdate('Y-m-d H:i', $timestamp);
            }
        }

        $performedByLabel = '';
        if (is_array($performedBy)) {
            $parts = [];
            if (!empty($performedBy['username'])) {
                $parts[] = (string) $performedBy['username'];
            }
            if (isset($performedBy['id'])) {
                $parts[] = '#' . (int) $performedBy['id'];
            }
            if (!empty($performedBy['role'])) {
                $parts[] = ucfirst((string) $performedBy['role']);
            }
            $performedByLabel = implode(' · ', $parts);
        }

        $details = $entry['details'] ?? [];
        $contextDetails = $entry['context'] ?? [];
        $detailsJson = '';
        if (!empty($details)) {
            $encoded = json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($encoded !== false) {
                $detailsJson = $encoded;
            }
        }

        $contextJson = '';
        if (!empty($contextDetails) && is_array($contextDetails)) {
            $encoded = json_encode($contextDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($encoded !== false) {
                $contextJson = $encoded;
            }
        }

        $ipAddress = (string) ($entry['ip_address'] ?? '');
        $userAgent = (string) ($entry['user_agent'] ?? '');
        $userAgentDisplay = $userAgent;
        if ($userAgent !== '' && strlen($userAgent) > 160) {
            $userAgentDisplay = substr($userAgent, 0, 157) . '...';
        }

        $activityRecords[] = [
            'id' => (int) ($entry['id'] ?? 0),
            'category' => $categoryName,
            'action' => $actionName,
            'dataset' => $datasetName,
            'dataset_label' => $datasetName !== '' ? ($datasetLabels[$datasetName] ?? $datasetName) : '',
            'created_at' => $createdAt,
            'created_at_display' => $createdAtDisplay,
            'trigger' => $entry['trigger'] ?? ($entry['details']['trigger'] ?? ''),
            'performed_by_display' => $performedByLabel,
            'details_json' => $detailsJson,
            'context_json' => $contextJson,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'user_agent_display' => $userAgentDisplay,
        ];

        if (count($activityRecords) >= $activityLimit) {
            break;
        }
    }

    fg_render_setup_page([
        'message' => $message,
        'errors' => $errors,
        'configurations' => $configurations,
        'overrides' => $overrides,
        'roles' => $roles,
        'users' => $users,
        'datasets' => $datasets,
        'themes' => $themesData,
        'theme_tokens' => $themeTokens,
        'default_theme' => $defaultThemeSetting,
        'theme_policy' => $themePolicy,
        'translations' => $translations,
        'project_status' => $projectStatus,
        'changelog' => $changelog,
        'feature_requests' => $featureRequests,
        'bug_reports' => $bugReports,
        'polls' => $polls,
        'poll_statuses' => $pollStatusOptions,
        'poll_policy' => $pollPolicy,
        'poll_default_visibility' => $pollDefaultVisibility,
        'poll_allow_multiple_default' => $pollAllowMultipleDefault,
        'events' => $events,
        'event_status_options' => $eventStatusOptions,
        'event_policy' => $eventPolicy,
        'event_default_visibility' => $eventDefaultVisibility,
        'event_rsvp_policy' => $eventRsvpPolicy,
        'event_default_timezone' => $eventDefaultTimezone,
        'event_feed_display_limit' => $eventFeedDisplayLimit,
        'automations' => $automations,
        'automation_statuses' => $automationStatusOptions,
        'automation_default_status' => $automationDefaultStatus,
        'automation_triggers' => $automationTriggerOptions,
        'automation_action_types' => $automationActionTypes,
        'automation_condition_types' => $automationConditionTypes,
        'automation_priority_options' => $automationPriorityOptions,
        'automation_default_owner_role' => $automationDefaultOwnerRole,
        'knowledge_base' => $knowledgeBase,
        'knowledge_categories' => $knowledgeCategories,
        'knowledge_default_status' => $knowledgeDefaultStatus,
        'knowledge_default_visibility' => $knowledgeDefaultVisibility,
        'knowledge_default_category' => $knowledgeDefaultCategoryId,
        'content_modules' => $contentModules,
        'content_blueprints' => $contentBlueprints,
        'feature_request_statuses' => $featureRequestStatusOptions,
        'feature_request_priorities' => $featureRequestPriorityOptions,
        'feature_request_policy' => $featureRequestPolicy,
        'feature_request_default_visibility' => $featureRequestDefaultVisibility,
        'bug_report_statuses' => $bugStatusOptions,
        'bug_report_severities' => $bugSeverityOptions,
        'bug_report_policy' => $bugPolicy,
        'bug_report_default_visibility' => $bugDefaultVisibility,
        'bug_report_default_owner_role' => $bugDefaultOwnerRole,
        'bug_report_feed_display_limit' => $bugFeedDisplayLimit,
        'locale_policy' => fg_get_setting('locale_personalisation_policy', 'enabled'),
        'default_locale' => fg_get_setting('default_locale', $translations['fallback_locale'] ?? 'en'),
        'pages' => fg_load_pages(),
        'activity_records' => $activityRecords,
        'activity_filters' => $activityFilters,
        'activity_limit' => $activityLimit,
        'activity_total' => count($activityRecordsRaw),
        'activity_dataset_labels' => $datasetLabels,
        'activity_categories' => array_keys($activityCategories),
        'activity_actions' => array_keys($activityActions),
    ]);
}
