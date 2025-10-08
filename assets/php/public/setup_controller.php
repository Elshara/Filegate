<?php

require_once __DIR__ . '/../global/bootstrap.php';
require_once __DIR__ . '/../global/require_login.php';
require_once __DIR__ . '/../global/is_admin.php';
require_once __DIR__ . '/../global/load_asset_configurations.php';
require_once __DIR__ . '/../global/load_asset_overrides.php';
require_once __DIR__ . '/../global/update_asset_configuration.php';
require_once __DIR__ . '/../global/update_asset_permissions.php';
require_once __DIR__ . '/../global/update_asset_override.php';
require_once __DIR__ . '/../global/clear_asset_override.php';
require_once __DIR__ . '/../global/load_settings.php';
require_once __DIR__ . '/../global/get_setting.php';
require_once __DIR__ . '/../global/load_users.php';
require_once __DIR__ . '/../global/load_dataset_manifest.php';
require_once __DIR__ . '/../global/dataset_format.php';
require_once __DIR__ . '/../global/dataset_nature.php';
require_once __DIR__ . '/../global/dataset_path.php';
require_once __DIR__ . '/../global/ensure_data_directory.php';
require_once __DIR__ . '/../global/load_dataset_contents.php';
require_once __DIR__ . '/../global/save_dataset_contents.php';
require_once __DIR__ . '/../global/dataset_default_payload.php';
require_once __DIR__ . '/../global/format_file_size.php';
require_once __DIR__ . '/../global/load_theme_tokens.php';
require_once __DIR__ . '/../global/load_themes.php';
require_once __DIR__ . '/../global/save_themes.php';
require_once __DIR__ . '/../global/default_themes_dataset.php';
require_once __DIR__ . '/../global/save_settings.php';
require_once __DIR__ . '/../global/load_pages.php';
require_once __DIR__ . '/../global/add_page.php';
require_once __DIR__ . '/../global/update_page.php';
require_once __DIR__ . '/../global/delete_page.php';
require_once __DIR__ . '/../global/default_pages_dataset.php';
require_once __DIR__ . '/../global/save_pages.php';
require_once __DIR__ . '/../global/load_project_status.php';
require_once __DIR__ . '/../global/default_project_status_dataset.php';
require_once __DIR__ . '/../global/add_project_status.php';
require_once __DIR__ . '/../global/update_project_status.php';
require_once __DIR__ . '/../global/delete_project_status.php';
require_once __DIR__ . '/../pages/render_setup.php';
require_once __DIR__ . '/../global/guard_asset.php';
require_once __DIR__ . '/../global/load_asset_snapshots.php';
require_once __DIR__ . '/../global/record_dataset_snapshot.php';
require_once __DIR__ . '/../global/list_dataset_snapshots.php';
require_once __DIR__ . '/../global/restore_dataset_snapshot.php';
require_once __DIR__ . '/../global/delete_dataset_snapshot.php';
require_once __DIR__ . '/../global/load_activity_log.php';
require_once __DIR__ . '/../global/load_translations.php';
require_once __DIR__ . '/../global/save_translations.php';
require_once __DIR__ . '/../global/normalize_translation_token_key.php';
require_once __DIR__ . '/../global/default_translations_dataset.php';

function fg_public_setup_controller(): void
{
    fg_bootstrap();
    $current = fg_require_login();
    fg_guard_asset('assets/php/public/setup_controller.php', [
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
        ]);
        return;
    }

    $message = '';
    $errors = [];
    fg_ensure_data_directory();
    $manifest = fg_load_dataset_manifest();
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
