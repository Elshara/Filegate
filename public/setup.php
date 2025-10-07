<?php

require_once __DIR__ . '/../assets/php/global/bootstrap.php';
require_once __DIR__ . '/../assets/php/global/require_login.php';
require_once __DIR__ . '/../assets/php/global/is_admin.php';
require_once __DIR__ . '/../assets/php/global/load_asset_configurations.php';
require_once __DIR__ . '/../assets/php/global/load_asset_overrides.php';
require_once __DIR__ . '/../assets/php/global/update_asset_configuration.php';
require_once __DIR__ . '/../assets/php/global/update_asset_permissions.php';
require_once __DIR__ . '/../assets/php/global/update_asset_override.php';
require_once __DIR__ . '/../assets/php/global/clear_asset_override.php';
require_once __DIR__ . '/../assets/php/global/load_settings.php';
require_once __DIR__ . '/../assets/php/global/load_users.php';
require_once __DIR__ . '/../assets/php/pages/render_setup.php';
require_once __DIR__ . '/../assets/php/global/guard_asset.php';

fg_bootstrap();
$current = fg_require_login();
fg_guard_asset('public/setup.php', [
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
    ]);
    return;
}

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_override'] ?? ($_POST['action'] ?? '');
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

$configurations = fg_load_asset_configurations();
$overrides = fg_load_asset_overrides();
$settings = fg_load_settings();
$roles = $settings['role_definitions'] ?? [];
$users = fg_load_users()['records'] ?? [];

fg_sync_public_assets();

fg_render_setup_page([
    'message' => $message,
    'errors' => $errors,
    'configurations' => $configurations,
    'overrides' => $overrides,
    'roles' => $roles,
    'users' => $users,
]);
