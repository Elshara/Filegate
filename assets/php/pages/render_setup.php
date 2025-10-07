<?php

require_once __DIR__ . '/../global/render_layout.php';

function fg_render_setup_page(array $data = []): void
{
    $configurations = $data['configurations']['records'] ?? [];
    $overrides = $data['overrides']['records'] ?? ['global' => [], 'roles' => [], 'users' => []];
    $roles = $data['roles'] ?? [];
    $users = $data['users'] ?? [];
    $datasets = $data['datasets'] ?? [];
    $message = $data['message'] ?? '';
    $errors = $data['errors'] ?? [];

    $userIndex = [];
    foreach ($users as $user) {
        $userIndex[(string) ($user['id'] ?? '')] = $user;
    }

    $body = '<section class="setup-intro">';
    $body .= '<h1>Asset Setup</h1>';
    $body .= '<p>Configure default parameters, permissions, and overrides for every asset without editing files directly.</p>';
    if ($message !== '') {
        $body .= '<div class="notice success">' . htmlspecialchars($message) . '</div>';
    }
    if (!empty($errors)) {
        $body .= '<div class="notice error"><ul>';
        foreach ($errors as $error) {
            $body .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $body .= '</ul></div>';
    }
    $body .= '</section>';

    $body .= '<div class="asset-setup-grid">';

    foreach ($configurations as $asset => $configuration) {
        $parameters = $configuration['parameters'] ?? [];
        $allowedRoles = $configuration['allowed_roles'] ?? [];
        $allowUserOverride = !empty($configuration['allow_user_override']);
        $mirrorOf = $configuration['mirror_of'] ?? null;
        $isMirror = is_string($mirrorOf) && $mirrorOf !== '';

        $articleAttributes = 'class="asset-card" data-asset="' . htmlspecialchars($asset) . '"';
        if ($isMirror) {
            $articleAttributes .= ' data-mirror="true"';
        }

        $body .= '<article ' . $articleAttributes . '>';
        $body .= '<header><h2>' . htmlspecialchars($configuration['label'] ?? $asset) . '</h2>';
        $body .= '<p class="asset-meta">' . htmlspecialchars($asset) . ' · Scope: ' . htmlspecialchars($configuration['scope'] ?? 'global') . ' · Extension: ' . htmlspecialchars($configuration['extension'] ?? '') . '</p>';
        if ($isMirror) {
            $body .= '<p class="asset-meta-note">Mirrors <code>' . htmlspecialchars($mirrorOf) . '</code> and stays synchronised automatically.</p>';
        }
        $body .= '</header>';

        $body .= '<section class="asset-section">';
        $body .= '<h3>Defaults &amp; Permissions</h3>';
        $body .= '<form method="post" action="/setup.php" class="asset-form">';
        $body .= '<input type="hidden" name="action" value="update_defaults">';
        $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($asset) . '">';
        $body .= '<div class="field-grid">';

        foreach ($parameters as $key => $definition) {
            $label = $definition['label'] ?? $key;
            $type = $definition['type'] ?? 'text';
            $defaultValue = $definition['default'] ?? '';
            $description = $definition['description'] ?? '';

            $body .= '<label class="field">';
            $body .= '<span class="field-label">' . htmlspecialchars($label) . '</span>';
            if ($type === 'boolean') {
                $checked = $defaultValue ? ' checked' : '';
                $body .= '<span class="field-control"><input type="checkbox" name="defaults[' . htmlspecialchars($key) . ']" value="1"' . $checked . '></span>';
            } elseif ($type === 'select') {
                $body .= '<span class="field-control"><select name="defaults[' . htmlspecialchars($key) . ']">';
                $options = $definition['options'] ?? [];
                foreach ($options as $option) {
                    $selected = ((string) $defaultValue === (string) $option) ? ' selected' : '';
                    $body .= '<option value="' . htmlspecialchars((string) $option) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $option)) . '</option>';
                }
                $body .= '</select></span>';
            } else {
                $body .= '<span class="field-control"><input type="text" name="defaults[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars((string) $defaultValue) . '"></span>';
            }
            if ($description !== '') {
                $body .= '<span class="field-description">' . htmlspecialchars($description) . '</span>';
            }
            $body .= '</label>';
        }

        $body .= '</div>';
        $body .= '<fieldset class="field-group">';
        $body .= '<legend>Roles allowed to manage overrides</legend>';
        $body .= '<div class="field-checkbox-group">';
        foreach ($roles as $role => $roleDescription) {
            $checked = in_array($role, $allowedRoles, true) ? ' checked' : '';
            $body .= '<label><input type="checkbox" name="allowed_roles[]" value="' . htmlspecialchars($role) . '"' . $checked . ($isMirror ? ' disabled' : '') . '> ' . htmlspecialchars(ucfirst($role)) . '</label>';
        }
        $body .= '</div>';
        $checkedOverride = $allowUserOverride ? ' checked' : '';
        $body .= '<label class="field-toggle"><input type="checkbox" name="allow_user_override" value="1"' . $checkedOverride . ($isMirror ? ' disabled' : '') . '> Allow members to personalise this asset</label>';
        $body .= '</fieldset>';
        $body .= '<button type="submit" class="button primary">Save defaults</button>';
        $body .= '</form>';
        $body .= '</section>';

        if ($isMirror) {
            $body .= '<section class="asset-section">';
            $body .= '<h3>Overrides</h3>';
            $body .= '<p class="asset-note">Configuration and overrides are inherited from <code>' . htmlspecialchars($mirrorOf) . '</code>. Adjust the source asset to change delivery.</p>';
            $body .= '</section>';
        } else {
                $body .= '<section class="asset-section">';
                $body .= '<h3>Global override</h3>';
                $globalValues = $overrides['global'][$asset] ?? [];
                $body .= '<form method="post" action="/setup.php" class="asset-form">';
                $body .= '<input type="hidden" name="action" value="update_override">';
                $body .= '<input type="hidden" name="scope" value="global">';
                $body .= '<input type="hidden" name="identifier" value="global">';
                $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($asset) . '">';
                $body .= '<div class="field-grid">';
                foreach ($parameters as $key => $definition) {
                    $label = $definition['label'] ?? $key;
                    $type = $definition['type'] ?? 'text';
                    $value = $globalValues[$key] ?? ($definition['default'] ?? '');
                    $description = $definition['description'] ?? '';

                    $body .= '<label class="field">';
                    $body .= '<span class="field-label">' . htmlspecialchars($label) . '</span>';
                    if ($type === 'boolean') {
                        $checked = $value ? ' checked' : '';
                        $body .= '<span class="field-control"><input type="checkbox" name="override[' . htmlspecialchars($key) . ']" value="1"' . $checked . '></span>';
                    } elseif ($type === 'select') {
                        $body .= '<span class="field-control"><select name="override[' . htmlspecialchars($key) . ']">';
                        $options = $definition['options'] ?? [];
                        foreach ($options as $option) {
                            $selected = ((string) $value === (string) $option) ? ' selected' : '';
                            $body .= '<option value="' . htmlspecialchars((string) $option) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $option)) . '</option>';
                        }
                        $body .= '</select></span>';
                    } else {
                        $body .= '<span class="field-control"><input type="text" name="override[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars((string) $value) . '"></span>';
                    }
                    if ($description !== '') {
                        $body .= '<span class="field-description">' . htmlspecialchars($description) . '</span>';
                    }
                    $body .= '</label>';
                }
                $body .= '</div>';
                $body .= '<div class="action-row">';
                $body .= '<button type="submit" class="button">Save override</button>';
                $body .= '</div>';
                $body .= '</form>';
                if (!empty($globalValues)) {
                    $body .= '<form method="post" action="/setup.php" class="asset-form inline">';
                    $body .= '<input type="hidden" name="action" value="clear_override">';
                    $body .= '<input type="hidden" name="scope" value="global">';
                    $body .= '<input type="hidden" name="identifier" value="global">';
                    $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($asset) . '">';
                    $body .= '<button type="submit" class="button danger">Remove global override</button>';
                    $body .= '</form>';
                }
                $body .= '</section>';

                $body .= '<section class="asset-section">';
                $body .= '<h3>Role overrides</h3>';
                foreach ($roles as $role => $roleDescription) {
                    $roleValues = $overrides['roles'][$role][$asset] ?? [];
                    $body .= '<form method="post" action="/setup.php" class="asset-form role-form">';
                    $body .= '<input type="hidden" name="action" value="update_override">';
                    $body .= '<input type="hidden" name="scope" value="roles">';
                    $body .= '<input type="hidden" name="identifier" value="' . htmlspecialchars($role) . '">';
                    $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($asset) . '">';
                    $body .= '<fieldset>';
                    $body .= '<legend>' . htmlspecialchars(ucfirst($role)) . '</legend>';
                    $body .= '<div class="field-grid">';
                    foreach ($parameters as $key => $definition) {
                        $label = $definition['label'] ?? $key;
                        $type = $definition['type'] ?? 'text';
                        $value = $roleValues[$key] ?? ($definition['default'] ?? '');

                        $body .= '<label class="field">';
                        $body .= '<span class="field-label">' . htmlspecialchars($label) . '</span>';
                        if ($type === 'boolean') {
                            $checked = $value ? ' checked' : '';
                            $body .= '<span class="field-control"><input type="checkbox" name="override[' . htmlspecialchars($key) . ']" value="1"' . $checked . '></span>';
                        } elseif ($type === 'select') {
                            $body .= '<span class="field-control"><select name="override[' . htmlspecialchars($key) . ']">';
                            $options = $definition['options'] ?? [];
                            foreach ($options as $option) {
                                $selected = ((string) $value === (string) $option) ? ' selected' : '';
                                $body .= '<option value="' . htmlspecialchars((string) $option) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $option)) . '</option>';
                            }
                            $body .= '</select></span>';
                        } else {
                            $body .= '<span class="field-control"><input type="text" name="override[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars((string) $value) . '"></span>';
                        }
                        $body .= '</label>';
                    }
                    $body .= '</div>';
                    $body .= '<div class="action-row">';
                    $body .= '<button type="submit" class="button">Save role override</button>';
                    if (!empty($roleValues)) {
                        $body .= '<button type="submit" name="action_override" value="clear_override" class="button danger">Clear</button>';
                    }
                    $body .= '</div>';
                    $body .= '</fieldset>';
                    $body .= '</form>';
                }
                $body .= '</section>';

                $body .= '<section class="asset-section">';
                $body .= '<h3>User overrides</h3>';
                $userOverrides = $overrides['users'] ?? [];
                foreach ($userOverrides as $userId => $assetsOverrides) {
                    if (!isset($assetsOverrides[$asset])) {
                        continue;
                    }
                    $values = $assetsOverrides[$asset];
                    $user = $userIndex[(string) $userId] ?? ['display_name' => 'User #' . $userId];
                    $displayName = $user['display_name'] ?? ($user['username'] ?? ('User #' . $userId));
                    $body .= '<form method="post" action="/setup.php" class="asset-form user-form">';
                    $body .= '<input type="hidden" name="action" value="update_override">';
                    $body .= '<input type="hidden" name="scope" value="users">';
                    $body .= '<input type="hidden" name="identifier" value="' . htmlspecialchars((string) $userId) . '">';
                    $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($asset) . '">';
                    $body .= '<fieldset>';
                    $body .= '<legend>' . htmlspecialchars($displayName) . '</legend>';
                    $body .= '<div class="field-grid">';
                    foreach ($parameters as $key => $definition) {
                        $label = $definition['label'] ?? $key;
                        $type = $definition['type'] ?? 'text';
                        $value = $values[$key] ?? ($definition['default'] ?? '');
                        $body .= '<label class="field">';
                        $body .= '<span class="field-label">' . htmlspecialchars($label) . '</span>';
                        if ($type === 'boolean') {
                            $checked = $value ? ' checked' : '';
                            $body .= '<span class="field-control"><input type="checkbox" name="override[' . htmlspecialchars($key) . ']" value="1"' . $checked . '></span>';
                        } elseif ($type === 'select') {
                            $body .= '<span class="field-control"><select name="override[' . htmlspecialchars($key) . ']">';
                            $options = $definition['options'] ?? [];
                            foreach ($options as $option) {
                                $selected = ((string) $value === (string) $option) ? ' selected' : '';
                                $body .= '<option value="' . htmlspecialchars((string) $option) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $option)) . '</option>';
                            }
                            $body .= '</select></span>';
                        } else {
                            $body .= '<span class="field-control"><input type="text" name="override[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars((string) $value) . '"></span>';
                        }
                        $body .= '</label>';
                    }
                    $body .= '</div>';
                    $body .= '<div class="action-row">';
                    $body .= '<button type="submit" class="button">Save user override</button>';
                    $body .= '<button type="submit" name="action_override" value="clear_override" class="button danger">Remove override</button>';
                    $body .= '</div>';
                    $body .= '</fieldset>';
                    $body .= '</form>';
                }

                if (!empty($users)) {
                    $body .= '<form method="post" action="/setup.php" class="asset-form user-form">';
                    $body .= '<input type="hidden" name="action" value="update_override">';
                    $body .= '<input type="hidden" name="scope" value="users">';
                    $body .= '<input type="hidden" name="asset" value="' . htmlspecialchars($asset) . '">';
                    $body .= '<fieldset>';
                    $body .= '<legend>Create or update user override</legend>';
                    $body .= '<label class="field">';
                    $body .= '<span class="field-label">Select user</span>';
                    $body .= '<span class="field-control"><select name="identifier">';
                    foreach ($users as $user) {
                        $id = (string) ($user['id'] ?? '');
                        $displayName = $user['display_name'] ?? ($user['username'] ?? $id);
                        $body .= '<option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($displayName) . '</option>';
                    }
                    $body .= '</select></span>';
                    $body .= '</label>';
                    $body .= '<div class="field-grid">';
                    foreach ($parameters as $key => $definition) {
                        $label = $definition['label'] ?? $key;
                        $type = $definition['type'] ?? 'text';
                        $value = $definition['default'] ?? '';
                        $body .= '<label class="field">';
                        $body .= '<span class="field-label">' . htmlspecialchars($label) . '</span>';
                        if ($type === 'boolean') {
                            $body .= '<span class="field-control"><input type="checkbox" name="override[' . htmlspecialchars($key) . ']" value="1"></span>';
                        } elseif ($type === 'select') {
                            $body .= '<span class="field-control"><select name="override[' . htmlspecialchars($key) . ']">';
                            $options = $definition['options'] ?? [];
                            foreach ($options as $option) {
                                $selected = ((string) $value === (string) $option) ? ' selected' : '';
                                $body .= '<option value="' . htmlspecialchars((string) $option) . '"' . $selected . '>' . htmlspecialchars(ucfirst((string) $option)) . '</option>';
                            }
                            $body .= '</select></span>';
                        } else {
                            $body .= '<span class="field-control"><input type="text" name="override[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars((string) $value) . '"></span>';
                        }
                        $body .= '</label>';
                    }
                    $body .= '</div>';
                    $body .= '<div class="action-row">';
                    $body .= '<button type="submit" class="button">Save user override</button>';
                    $body .= '</div>';
                    $body .= '</fieldset>';
                    $body .= '</form>';
                }

                $body .= '</section>';

        }

        $body .= '</article>';
    }

    $body .= '</div>';

    if (!empty($datasets)) {
        $body .= '<section class="dataset-manager">';
        $body .= '<h2>Dataset Management</h2>';
        $body .= '<p>Review and regenerate the local datasets that power Filegate. Upload replacements or edit the payloads directly without leaving the browser.</p>';

        foreach ($datasets as $dataset) {
            $datasetName = $dataset['name'] ?? '';
            $label = $dataset['label'] ?? $datasetName;
            $description = $dataset['description'] ?? '';
            $nature = $dataset['nature'] ?? 'dynamic';
            $format = $dataset['format'] ?? 'json';
            $size = $dataset['size'] ?? '0 B';
            $modified = $dataset['modified'] ?? '';
            $payload = $dataset['payload'] ?? '';
            $rows = (int) ($dataset['rows'] ?? 12);
            $editable = !empty($dataset['editable']);
            $missing = !empty($dataset['missing']);
            $hasDefaults = !empty($dataset['has_defaults']);

            $detailsAttributes = 'class="dataset-card" data-dataset="' . htmlspecialchars($datasetName) . '" data-nature="' . htmlspecialchars($nature) . '" data-format="' . htmlspecialchars($format) . '"';
            if ($missing) {
                $detailsAttributes .= ' open';
            }

            $body .= '<details ' . $detailsAttributes . '>';
            $body .= '<summary>';
            $body .= '<span class="dataset-label">' . htmlspecialchars($label) . '</span>';
            $body .= '<span class="dataset-meta">';
            $body .= '<span class="dataset-chip">Nature: ' . htmlspecialchars(ucfirst($nature)) . '</span>';
            $body .= '<span class="dataset-chip">Format: ' . htmlspecialchars(strtoupper($format)) . '</span>';
            $body .= '<span class="dataset-chip">Size: ' . htmlspecialchars($size) . '</span>';
            $body .= '<span class="dataset-chip">Updated: ' . htmlspecialchars($modified) . '</span>';
            if ($missing) {
                $body .= '<span class="dataset-chip warning">Not generated</span>';
            }
            $body .= '</span>';
            $body .= '</summary>';

            $body .= '<div class="dataset-body">';
            if ($description !== '') {
                $body .= '<p class="dataset-description">' . htmlspecialchars($description) . '</p>';
            }

            if (!$editable) {
                $body .= '<p class="notice muted">This dataset is currently read-only because the target path is not writable. Adjust filesystem permissions to modify it from the browser.</p>';
            }

            $body .= '<form method="post" action="/setup.php" class="dataset-form" enctype="multipart/form-data">';
            $body .= '<input type="hidden" name="action" value="save_dataset">';
            $body .= '<input type="hidden" name="dataset" value="' . htmlspecialchars($datasetName) . '">';
            $textareaAttributes = 'name="dataset_payload" rows="' . $rows . '"';
            if (!$editable) {
                $textareaAttributes .= ' readonly';
            }
            $body .= '<label class="field">';
            $body .= '<span class="field-label">Dataset payload</span>';
            $body .= '<span class="field-control"><textarea ' . $textareaAttributes . '>' . htmlspecialchars($payload) . '</textarea></span>';
            $body .= '<span class="field-description">Paste or edit the dataset contents directly. For XML datasets, provide complete XML markup.</span>';
            $body .= '</label>';

            $body .= '<label class="field upload-field">';
            $body .= '<span class="field-label">Upload replacement</span>';
            $fileAttributes = 'type="file" name="dataset_file" accept=".' . htmlspecialchars($format) . '"';
            if (!$editable) {
                $fileAttributes .= ' disabled';
            }
            $body .= '<span class="field-control"><input ' . $fileAttributes . '></span>';
            $body .= '<span class="field-description">Choose a local .' . htmlspecialchars($format) . ' file to replace the current dataset. Uploaded content overrides any text edits above.</span>';
            $body .= '</label>';

            $body .= '<div class="action-row">';
            if ($editable) {
                $body .= '<button type="submit" class="button primary">Save dataset</button>';
            } else {
                $body .= '<button type="submit" class="button" disabled>Save dataset</button>';
            }
            if ($hasDefaults && $editable) {
                $body .= '<button type="submit" name="action_override" value="reset_dataset" class="button danger">Reset to defaults</button>';
            }
            $body .= '</div>';
            $body .= '</form>';

            $body .= '</div>';
            $body .= '</details>';
        }

        $body .= '</section>';
    }

    fg_render_layout('Asset Setup', $body);
}
