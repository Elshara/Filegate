<?php

require_once __DIR__ . '/../global/load_settings.php';
require_once __DIR__ . '/../global/get_setting.php';
require_once __DIR__ . '/../global/can_manage_setting.php';
require_once __DIR__ . '/../global/list_datasets.php';
require_once __DIR__ . '/../global/is_admin.php';
require_once __DIR__ . '/../global/render_layout.php';
require_once __DIR__ . '/../global/load_json.php';
require_once __DIR__ . '/../global/seed_defaults.php';
require_once __DIR__ . '/../global/dataset_is_exposable.php';
require_once __DIR__ . '/../global/dataset_format.php';
require_once __DIR__ . '/../global/dataset_path.php';
require_once __DIR__ . '/../global/load_asset_configurations.php';
require_once __DIR__ . '/../global/load_asset_overrides.php';
require_once __DIR__ . '/../global/get_asset_parameter_value.php';
require_once __DIR__ . '/../global/asset_label.php';

function fg_render_settings_page(array $user, array $context = []): void
{
    fg_seed_defaults();
    $settings = fg_load_settings();
    $message = $context['message'] ?? '';
    $error = $context['error'] ?? '';

    $body = '<section class="panel settings-grid">';
    $body .= '<h1>Settings</h1>';
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

    $body .= '</section>';

    fg_render_layout('Settings', $body);
}

