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
require_once __DIR__ . '/../pages/render_setup.php';
require_once __DIR__ . '/../global/guard_asset.php';

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
        ]);
        return;
    }

    $message = '';
    $errors = [];
    fg_ensure_data_directory();
    $manifest = fg_load_dataset_manifest();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action_override'] ?? ($_POST['action'] ?? '');
        if (in_array($action, ['save_dataset', 'reset_dataset'], true)) {
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
                            fg_save_dataset_contents($dataset, $payload);
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
                            fg_save_dataset_contents($dataset, $defaultPayload);
                            $message = 'Dataset ' . $dataset . ' has been reset to its defaults.';
                        } catch (Throwable $exception) {
                            $errors[] = $exception->getMessage();
                        }
                    }
                }
            }
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
        ];
    }

    fg_render_setup_page([
        'message' => $message,
        'errors' => $errors,
        'configurations' => $configurations,
        'overrides' => $overrides,
        'roles' => $roles,
        'users' => $users,
        'datasets' => $datasets,
    ]);
}
