<?php

require_once __DIR__ . '/load_settings.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/can_manage_setting.php';
require_once __DIR__ . '/list_datasets.php';
require_once __DIR__ . '/is_admin.php';
require_once __DIR__ . '/render_layout.php';
require_once __DIR__ . '/load_json.php';
require_once __DIR__ . '/seed_defaults.php';
require_once __DIR__ . '/dataset_is_exposable.php';
require_once __DIR__ . '/dataset_format.php';
require_once __DIR__ . '/dataset_path.php';
require_once __DIR__ . '/load_asset_configurations.php';
require_once __DIR__ . '/load_asset_overrides.php';
require_once __DIR__ . '/get_asset_parameter_value.php';
require_once __DIR__ . '/asset_label.php';
require_once __DIR__ . '/resolve_theme_tokens.php';
require_once __DIR__ . '/resolve_locale.php';
require_once __DIR__ . '/load_translations.php';
require_once __DIR__ . '/translate.php';

function fg_render_settings_page(array $user, array $context = []): void
{
    fg_seed_defaults();
    $settings = fg_load_settings();
    $message = $context['message'] ?? '';
    $error = $context['error'] ?? '';

    $localeInfo = fg_resolve_locale($user);
    $heading = fg_translate('settings.heading', [
        'user' => $user,
        'locale' => $localeInfo['locale'],
        'fallback_locale' => $localeInfo['fallback_locale'],
        'default' => 'Settings',
    ]);

    $body = '<section class="panel settings-grid">';
    $body .= '<h1>' . htmlspecialchars($heading) . '</h1>';
    if ($message !== '') {
        $body .= '<p class="success">' . htmlspecialchars($message) . '</p>';
    }
    if ($error !== '') {
        $body .= '<p class="error">' . htmlspecialchars($error) . '</p>';
    }

    $body .= '<section>';
    $body .= '<h2>Delegated controls</h2>';
    foreach ($settings['settings'] as $key => $setting) {
        $body .= '<details>'; 
        $body .= '<summary>' . htmlspecialchars($setting['label']) . '</summary>';
        $body .= '<p>' . htmlspecialchars($setting['description']) . '</p>';
        $body .= '<p><strong>Current value:</strong> ' . htmlspecialchars(is_array($setting['value']) ? json_encode($setting['value']) : (string) $setting['value']) . '</p>';
        $body .= '<p><strong>Managed by:</strong> ' . htmlspecialchars($setting['managed_by']) . '</p>';
        if (fg_can_manage_setting($user, $key)) {
            $body .= '<form method="post" action="/settings.php">';
            $body .= '<input type="hidden" name="action" value="update-setting">';
            $body .= '<input type="hidden" name="setting" value="' . htmlspecialchars($key) . '">';
            $body .= '<label>New value<input type="text" name="value" value="' . htmlspecialchars(is_array($setting['value']) ? json_encode($setting['value']) : (string) $setting['value']) . '"></label>';
            $body .= '<button type="submit">Update</button>';
            $body .= '</form>';
        }
        if (($user['role'] ?? 'member') === 'admin') {
            $body .= '<form method="post" action="/settings.php">';
            $body .= '<input type="hidden" name="action" value="delegate-setting">';
            $body .= '<input type="hidden" name="setting" value="' . htmlspecialchars($key) . '">';
            $body .= '<label>Managed by<select name="managed_by">';
            foreach (['admins' => 'Admins only', 'everyone' => 'Everyone', 'custom' => 'Custom roles or people', 'none' => 'Locked'] as $value => $label) {
                $selected = $setting['managed_by'] === $value ? ' selected' : '';
                $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
            }
            $body .= '</select></label>';
            $body .= '<label>Allowed roles or people<input type="text" name="allowed" value="' . htmlspecialchars(implode(', ', $setting['allowed_roles'] ?? [])) . '" placeholder="admin, moderator, user:3"></label>';
            $body .= '<button type="submit">Delegate</button>';
            $body .= '</form>';
        }
        $body .= '</details>';
    }
    $body .= '</section>';

    $datasets = fg_list_datasets();

    if (($user['role'] ?? 'member') === 'admin') {
        $body .= '<section class="panel">';
        $body .= '<h2>Datasets</h2>';
        if ($datasets === []) {
            $body .= '<p class="error">No datasets are registered in the manifest.</p>';
        } else {
            $body .= '<table class="dataset-table">';
            $body .= '<thead><tr><th>Dataset</th><th>Description</th><th>Nature</th><th>Format</th><th>API access</th><th>Preview</th></tr></thead><tbody>';
            foreach ($datasets as $key => $definition) {
                $format = fg_dataset_format($key);
                if ($format === 'xml') {
                    $path_fragment = fg_dataset_path($key);
                    $preview_source = file_exists($path_fragment) ? file_get_contents($path_fragment) : '';
                } else {
                    $preview_source = json_encode(fg_load_json($key), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                }
                $snippet = $preview_source !== '' ? substr($preview_source, 0, 280) : '';
                if ($preview_source !== '' && strlen($preview_source) > 280) {
                    $snippet .= '…';
                }
                $preview = htmlspecialchars($snippet);
                $nature = isset($definition['nature']) ? ucfirst((string) $definition['nature']) : 'Dynamic';
                $exposed = fg_dataset_is_exposable($key);
                $output_id = 'dataset-live-' . preg_replace('/[^a-z0-9_-]/', '-', strtolower($key));
                $body .= '<tr>';
                $body .= '<td>' . htmlspecialchars($definition['label'] ?? $key) . '</td>';
                $body .= '<td>' . htmlspecialchars($definition['description'] ?? '') . '</td>';
                $body .= '<td>' . htmlspecialchars($nature) . '</td>';
                $body .= '<td>' . htmlspecialchars(strtoupper($format)) . '</td>';
                $body .= '<td>' . ($exposed ? 'Allowed' : 'Restricted') . '</td>';
                $body .= '<td><div class="dataset-actions"><button type="button" class="dataset-viewer" data-dataset="' . htmlspecialchars($key) . '" data-expose="' . ($exposed ? 'true' : 'false') . '" data-output="#' . htmlspecialchars($output_id) . '">Load live data</button><pre id="' . htmlspecialchars($output_id) . '" class="dataset-live-preview" data-dataset-output>' . $preview . '</pre></div></td>';
                $body .= '</tr>';
            }
            $body .= '</tbody></table>';
            $body .= '<form method="post" action="/settings.php">';
            $body .= '<input type="hidden" name="action" value="replace-dataset">';
            $body .= '<label>Select dataset<select name="dataset">';
            foreach ($datasets as $key => $definition) {
                if (fg_dataset_format($key) !== 'json') {
                    continue;
                }
                $body .= '<option value="' . htmlspecialchars($key) . '">' . htmlspecialchars($definition['label'] ?? $key) . '</option>';
            }
            $body .= '</select></label>';
            $body .= '<label>JSON payload<textarea name="payload" placeholder="{"example":true}" required></textarea></label>';
            $body .= '<button type="submit">Replace dataset</button>';
            $body .= '</form>';
        }
        $body .= '</section>';
    }

    $configurationsData = fg_load_asset_configurations();
    $overridesData = fg_load_asset_overrides();
    $role = $user['role'] ?? 'member';
    $userId = (string) ($user['id'] ?? '');
    $availableAssets = [];

    foreach ($configurationsData['records'] as $assetKey => $configuration) {
        if (empty($configuration['allow_user_override'])) {
            continue;
        }

        $allowedRoles = $configuration['allowed_roles'] ?? [];
        if ($role !== 'admin' && !in_array($role, $allowedRoles, true)) {
            continue;
        }

        $availableAssets[$assetKey] = $configuration;
    }

    if (!empty($availableAssets)) {
        $body .= '<section class="panel">';
        $body .= '<h2>Asset personalisation</h2>';
        $body .= '<p>Adjust how individual assets behave for your account. Your overrides sit on top of global and role-specific settings.</p>';

        $globalOverrides = $overridesData['records']['global'] ?? [];
        $roleOverrides = ($overridesData['records']['roles'][$role] ?? []);
        $userOverrides = $userId !== '' ? ($overridesData['records']['users'][$userId] ?? []) : [];

        foreach ($availableAssets as $assetKey => $configuration) {
            $parameters = $configuration['parameters'] ?? [];
            $assetLabel = fg_asset_label($assetKey);
            $body .= '<article class="asset-preference">';
            $body .= '<h3>' . htmlspecialchars($assetLabel) . '</h3>';
            $body .= '<p class="asset-meta">' . htmlspecialchars($assetKey) . ' · Scope: ' . htmlspecialchars($configuration['scope'] ?? 'local') . '</p>';
            $body .= '<form method="post" action="/settings.php" class="asset-form">';
            $body .= '<input type="hidden" name="action" value="update-asset-preferences">';
            $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($assetKey) . '">';
            $body .= '<div class="field-grid">';

            foreach ($parameters as $parameterKey => $definition) {
                $type = $definition['type'] ?? 'text';
                $defaultValue = $definition['default'] ?? '';
                $globalValue = $globalOverrides[$assetKey][$parameterKey] ?? null;
                $roleValue = $roleOverrides[$assetKey][$parameterKey] ?? null;
                $userValue = $userOverrides[$assetKey][$parameterKey] ?? null;
                $effective = fg_get_asset_parameter_value($assetKey, $parameterKey, [
                    'role' => $role,
                    'user_id' => $user['id'] ?? null,
                ]);

                $currentInputValue = $userValue;
                if ($currentInputValue === null) {
                    $currentInputValue = $effective;
                }

                $body .= '<label class="field">';
                $body .= '<span class="field-label">' . htmlspecialchars($definition['label'] ?? $parameterKey) . '</span>';

                if ($type === 'boolean') {
                    $checked = $currentInputValue ? ' checked' : '';
                    $body .= '<span class="field-control"><input type="checkbox" name="preferences[' . htmlspecialchars($parameterKey) . ']" value="1"' . $checked . '></span>';
                } elseif ($type === 'select') {
                    $body .= '<span class="field-control"><select name="preferences[' . htmlspecialchars($parameterKey) . ']">';
                    $options = $definition['options'] ?? [];
                    foreach ($options as $option) {
                        $selected = ((string) $currentInputValue === (string) $option) ? ' selected' : '';
                        $body .= '<option value="' . htmlspecialchars((string) $option) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $option)) . '</option>';
                    }
                    $body .= '</select></span>';
                } else {
                    $body .= '<span class="field-control"><input type="text" name="preferences[' . htmlspecialchars($parameterKey) . ']" value="' . htmlspecialchars((string) $currentInputValue) . '"></span>';
                }

                $defaultDisplay = $type === 'boolean' ? ($defaultValue ? 'Enabled' : 'Disabled') : (string) $defaultValue;
                $globalDisplay = $globalValue === null ? '—' : ($type === 'boolean' ? ($globalValue ? 'Enabled' : 'Disabled') : (string) $globalValue);
                $roleDisplay = $roleValue === null ? '—' : ($type === 'boolean' ? ($roleValue ? 'Enabled' : 'Disabled') : (string) $roleValue);
                $effectiveDisplay = $type === 'boolean' ? ($effective ? 'Enabled' : 'Disabled') : (string) $effective;
                $body .= '<span class="field-description">Default: ' . htmlspecialchars($defaultDisplay) . ' · Global: ' . htmlspecialchars($globalDisplay) . ' · Role: ' . htmlspecialchars($roleDisplay) . ' · Active: ' . htmlspecialchars($effectiveDisplay) . '</span>';
                $body .= '</label>';
            }

            $body .= '</div>';
            $body .= '<div class="action-row">';
            $body .= '<button type="submit" class="button primary">Save preferences</button>';
            if (!empty($userOverrides[$assetKey])) {
                $body .= '<button type="submit" name="action_override" value="clear-asset-preferences" class="button danger">Revert to delegated values</button>';
            }
            $body .= '</div>';
            $body .= '</form>';
            $body .= '</article>';
        }

        $body .= '</section>';
    }

    $themeResolution = fg_resolve_theme_tokens($user);
    $availableThemes = $themeResolution['available_themes'] ?? [];
    if (!empty($availableThemes)) {
        $body .= '<section class="panel theme-panel">';
        $body .= '<h2>Theme &amp; appearance</h2>';
        $body .= '<p>Preview and tune the palette used across Filegate. Changes apply instantly for your account after saving.</p>';

        $policy = fg_get_setting('theme_personalisation_policy', 'enabled');
        $activeThemeKey = $themeResolution['theme_key'] ?? '';
        $activeThemeLabel = $themeResolution['theme_label'] ?? $activeThemeKey;
        $overrides = $user['theme_preferences']['tokens'] ?? [];
        $selectedTheme = $user['theme_preferences']['theme'] ?? $activeThemeKey;

        $body .= '<p class="theme-active">Active theme: <strong>' . htmlspecialchars($activeThemeLabel) . '</strong></p>';

        if ($policy === 'disabled') {
            $body .= '<p class="notice warning">Administrators have disabled personal palette overrides. Your interface follows the delegated defaults.</p>';
        } else {
            $body .= '<form method="post" action="/settings.php" class="theme-form" data-theme-preview data-theme-preview-global data-theme-sync-select>';
            $body .= '<input type="hidden" name="action" value="update-theme-preferences">';
            $body .= '<label class="field">';
            $body .= '<span class="field-label">Theme preset</span>';
            $body .= '<span class="field-control"><select name="theme_key" data-theme-selector>';
            foreach ($availableThemes as $key => $themeDefinition) {
                $selected = ($selectedTheme === $key) ? ' selected' : '';
                $tokensPayload = htmlspecialchars(json_encode($themeDefinition['tokens'] ?? [], JSON_UNESCAPED_SLASHES), ENT_QUOTES);
                $body .= '<option value="' . htmlspecialchars($key) . '" data-theme-values="' . $tokensPayload . '"' . $selected . '>' . htmlspecialchars($themeDefinition['label'] ?? $key) . '</option>';
            }
            $body .= '</select></span>';
            $body .= '<span class="field-description">Switch between administrator curated palettes or customise the tokens below.</span>';
            $body .= '</label>';

            $body .= '<div class="theme-token-grid">';
            foreach ($themeResolution['tokens'] as $tokenKey => $tokenDefinition) {
                $type = $tokenDefinition['type'] ?? 'text';
                $value = $tokenDefinition['value'] ?? '';
                $label = $tokenDefinition['label'] ?? ucfirst($tokenKey);
                $description = $tokenDefinition['description'] ?? '';
                $cssVariable = $tokenDefinition['css_variable'] ?? ('--fg-' . str_replace('_', '-', $tokenKey));
                $body .= '<label class="field" data-theme-token="' . htmlspecialchars($tokenKey) . '">';
                $body .= '<span class="field-label">' . htmlspecialchars($label) . '</span>';
                if ($type === 'color') {
                    $body .= '<span class="field-control"><input type="color" name="tokens[' . htmlspecialchars($tokenKey) . ']" value="' . htmlspecialchars($value) . '" data-theme-token-input data-css-variable="' . htmlspecialchars($cssVariable) . '"></span>';
                } else {
                    $body .= '<span class="field-control"><input type="text" name="tokens[' . htmlspecialchars($tokenKey) . ']" value="' . htmlspecialchars($value) . '" data-theme-token-input data-css-variable="' . htmlspecialchars($cssVariable) . '"></span>';
                }
                $body .= '<span class="field-description">' . htmlspecialchars($description) . ' · CSS variable ' . htmlspecialchars($cssVariable) . '</span>';
                $body .= '</label>';
            }
            $body .= '</div>';

            $body .= '<div class="theme-preview" data-theme-preview-target>';
            $body .= '<div class="theme-preview-header">Preview</div>';
            $body .= '<p class="theme-preview-body">Text using the primary tone. Secondary copy adapts automatically.</p>';
            $body .= '<div class="accent">Accent example</div>';
            $body .= '<div class="swatch-row">';
            $body .= '<span class="swatch positive">Positive</span>';
            $body .= '<span class="swatch warning">Warning</span>';
            $body .= '<span class="swatch negative">Critical</span>';
            $body .= '</div>';
            $body .= '</div>';

            $body .= '<div class="action-row">';
            $body .= '<button type="submit" class="button primary">Save theme</button>';
            $body .= '<button type="button" class="button" data-theme-reset>Use theme defaults</button>';
            $body .= '</div>';
            $body .= '</form>';

            if (!empty($overrides) || (($user['theme_preferences']['theme'] ?? '') !== '')) {
                $body .= '<form method="post" action="/settings.php" class="theme-form inline">';
                $body .= '<input type="hidden" name="action" value="clear-theme-preferences">';
                $body .= '<button type="submit" class="button danger">Revert to delegated theme</button>';
                $body .= '</form>';
            }
        }

        $body .= '</section>';
    }

    $localePolicy = fg_get_setting('locale_personalisation_policy', 'enabled');
    $translations = fg_load_translations();
    $availableLocales = $translations['locales'] ?? [];
    $activeLocaleKey = $user['locale'] ?? $localeInfo['locale'];
    $activeLocaleLabel = $availableLocales[$activeLocaleKey]['label'] ?? $activeLocaleKey;

    if (!empty($availableLocales)) {
        $body .= '<section class="panel locale-panel">';
        $body .= '<h2>Locale</h2>';
        $body .= '<p class="notice muted">Active locale: <strong>' . htmlspecialchars((string) $activeLocaleLabel) . '</strong></p>';

        if ($localePolicy === 'disabled') {
            $body .= '<p class="notice">Locale personalisation is disabled. Administrators can adjust the policy from the setup dashboard.</p>';
        } else {
            $allowed = $localePolicy === 'enabled' || ($localePolicy === 'admins-only' && ($user['role'] ?? 'member') === 'admin');
            if ($allowed) {
                $body .= '<form method="post" action="/settings.php" class="locale-form">';
                $body .= '<input type="hidden" name="action" value="update-locale">';
                $body .= '<label class="field">';
                $body .= '<span class="field-label">Preferred locale</span>';
                $body .= '<span class="field-control"><select name="locale">';
                foreach ($availableLocales as $key => $definition) {
                    $label = $definition['label'] ?? $key;
                    $selected = ((string) $activeLocaleKey === (string) $key) ? ' selected' : '';
                    $body .= '<option value="' . htmlspecialchars((string) $key) . '"' . $selected . '>' . htmlspecialchars((string) $label) . '</option>';
                }
                $body .= '</select></span>';
                $body .= '<span class="field-description">Choose the interface language used throughout Filegate.</span>';
                $body .= '</label>';
                $body .= '<button type="submit" class="button primary">Save locale</button>';
                $body .= '</form>';
                $body .= '<form method="post" action="/settings.php" class="locale-reset">';
                $body .= '<input type="hidden" name="action" value="clear-locale">';
                $body .= '<button type="submit" class="button">Revert to delegated default</button>';
                $body .= '</form>';
            } else {
                $body .= '<p class="notice">Only administrators may change locale preferences while the current policy is active.</p>';
            }
        }

        $body .= '</section>';
    }

    $body .= '</section>';

    fg_render_layout('Settings', $body);
}

