<?php

require_once __DIR__ . '/../assets/php/global/bootstrap.php';
require_once __DIR__ . '/../assets/php/global/require_login.php';
require_once __DIR__ . '/../assets/php/global/update_setting.php';
require_once __DIR__ . '/../assets/php/global/delegate_setting.php';
require_once __DIR__ . '/../assets/php/global/list_datasets.php';
require_once __DIR__ . '/../assets/php/global/save_json.php';
require_once __DIR__ . '/../assets/php/global/render_layout.php';
require_once __DIR__ . '/../assets/php/global/parse_allowed_list.php';
require_once __DIR__ . '/../assets/php/global/normalize_setting_value.php';
require_once __DIR__ . '/../assets/php/global/load_asset_configurations.php';
require_once __DIR__ . '/../assets/php/global/update_asset_override.php';
require_once __DIR__ . '/../assets/php/global/clear_asset_override.php';
require_once __DIR__ . '/../assets/php/global/guard_asset.php';
require_once __DIR__ . '/../assets/php/pages/render_settings.php';

fg_bootstrap();
$user = fg_require_login();
fg_guard_asset('public/settings.php', [
    'role' => $user['role'] ?? null,
    'user_id' => $user['id'] ?? null,
]);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_override'] ?? ($_POST['action'] ?? '');

    if ($action === 'update-setting') {
        $setting = $_POST['setting'] ?? '';
        $value_raw = $_POST['value'] ?? '';
        $value = is_string($value_raw) ? fg_normalize_setting_value($value_raw) : $value_raw;
        if (fg_update_setting($setting, $value, $user)) {
            $message = 'Setting updated.';
        } else {
            $error = 'You are not allowed to update that setting.';
        }
    } elseif ($action === 'delegate-setting') {
        $setting = $_POST['setting'] ?? '';
        $managed = $_POST['managed_by'] ?? 'admins';
        $allowed = fg_parse_allowed_list($_POST['allowed'] ?? '');
        if (fg_delegate_setting($setting, $managed, $allowed, $user)) {
            $message = 'Delegation updated.';
        } else {
            $error = 'Unable to update delegation.';
        }
    } elseif ($action === 'replace-dataset') {
        if (($user['role'] ?? 'member') !== 'admin') {
            $error = 'Only admins may replace datasets.';
        } else {
            $dataset = $_POST['dataset'] ?? '';
            $payload = $_POST['payload'] ?? '';
            $definitions = fg_list_datasets();
            if (!isset($definitions[$dataset])) {
                $error = 'Unknown dataset selected.';
            } else {
                $decoded = json_decode($payload, true);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    $error = 'Invalid JSON payload: ' . json_last_error_msg();
                } else {
                    try {
                        fg_save_json($dataset, is_array($decoded) ? $decoded : ['value' => $decoded]);
                        $message = 'Dataset replaced.';
                    } catch (RuntimeException $exception) {
                        $error = $exception->getMessage();
                    }
                }
            }
        }
    } elseif ($action === 'update-asset-preferences') {
        $asset = $_POST['asset'] ?? '';
        $configurations = fg_load_asset_configurations();
        $configuration = $configurations['records'][$asset] ?? null;
        if ($configuration === null || empty($configuration['allow_user_override'])) {
            $error = 'Asset does not support personalisation.';
        } else {
            $role = $user['role'] ?? 'member';
            $allowedRoles = $configuration['allowed_roles'] ?? [];
            if ($role !== 'admin' && !in_array($role, $allowedRoles, true)) {
                $error = 'You are not permitted to override this asset.';
            } else {
                $preferences = $_POST['preferences'] ?? [];
                $parameters = $configuration['parameters'] ?? [];
                $normalized = [];
                foreach ($parameters as $key => $definition) {
                    $type = $definition['type'] ?? 'text';
                    if ($type === 'boolean') {
                        $normalized[$key] = isset($preferences[$key]);
                    } elseif ($type === 'select') {
                        $options = array_map('strval', $definition['options'] ?? []);
                        $value = $preferences[$key] ?? ($definition['default'] ?? '');
                        if (!in_array((string) $value, $options, true)) {
                            $value = $definition['default'] ?? '';
                        }
                        $normalized[$key] = $value;
                    } else {
                        $normalized[$key] = trim((string) ($preferences[$key] ?? ''));
                    }
                }
                $userId = (string) ($user['id'] ?? '');
                if ($userId === '') {
                    $error = 'User profile missing identifier.';
                } else {
                    fg_update_asset_override($asset, 'users', $userId, $normalized);
                    $message = 'Preferences saved.';
                }
            }
        }
    } elseif ($action === 'clear-asset-preferences') {
        $asset = $_POST['asset'] ?? '';
        $configurations = fg_load_asset_configurations();
        $configuration = $configurations['records'][$asset] ?? null;
        if ($configuration === null) {
            $error = 'Unknown asset provided for clearing preferences.';
        } else {
            $userId = (string) ($user['id'] ?? '');
            if ($userId === '') {
                $error = 'User profile missing identifier.';
            } else {
                fg_clear_asset_override($asset, 'users', $userId);
                $message = 'Asset preferences reverted.';
            }
        }
    }
}

fg_render_settings_page($user, ['message' => $message, 'error' => $error]);

