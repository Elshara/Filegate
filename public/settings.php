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
require_once __DIR__ . '/../assets/php/pages/render_settings.php';

fg_bootstrap();
$user = fg_require_login();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

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
    }
}

fg_render_settings_page($user, ['message' => $message, 'error' => $error]);

