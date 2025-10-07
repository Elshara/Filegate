<?php

require_once __DIR__ . '/../global/load_settings.php';
require_once __DIR__ . '/../global/get_setting.php';
require_once __DIR__ . '/../global/can_manage_setting.php';
require_once __DIR__ . '/../global/list_datasets.php';
require_once __DIR__ . '/../global/is_admin.php';
require_once __DIR__ . '/../global/render_layout.php';
require_once __DIR__ . '/../global/load_json.php';
require_once __DIR__ . '/../global/seed_defaults.php';

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

    if (($user['role'] ?? 'member') === 'admin') {
        $body .= '<section class="panel">';
        $body .= '<h2>Datasets</h2>';
        $body .= '<table class="dataset-table">';
        $body .= '<thead><tr><th>Dataset</th><th>Description</th><th>Preview</th></tr></thead><tbody>';
        foreach (fg_list_datasets() as $key => $definition) {
            $preview_json = json_encode(fg_load_json($key), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $preview = htmlspecialchars(substr($preview_json ?: '[]', 0, 280)) . '…';
            $body .= '<tr>';
            $body .= '<td>' . htmlspecialchars($definition['label']) . '</td>';
            $body .= '<td>' . htmlspecialchars($definition['description']) . '</td>';
            $body .= '<td><pre>' . $preview . '</pre></td>';
            $body .= '</tr>';
        }
        $body .= '</tbody></table>';
        $body .= '<form method="post" action="/settings.php">';
        $body .= '<input type="hidden" name="action" value="replace-dataset">';
        $body .= '<label>Select dataset<select name="dataset">';
        foreach (fg_list_datasets() as $key => $definition) {
            $body .= '<option value="' . htmlspecialchars($key) . '">' . htmlspecialchars($definition['label']) . '</option>';
        }
        $body .= '</select></label>';
        $body .= '<label>JSON payload<textarea name="payload" placeholder="{"example":true}" required></textarea></label>';
        $body .= '<button type="submit">Replace dataset</button>';
        $body .= '</form>';
        $body .= '</section>';
    }

    $body .= '</section>';

    fg_render_layout('Settings', $body);
}

