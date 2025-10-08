<?php

require_once __DIR__ . '/../global/render_layout.php';
require_once __DIR__ . '/../global/default_translations_dataset.php';

function fg_render_setup_page(array $data = []): void
{
    $configurations = $data['configurations']['records'] ?? [];
    $overrides = $data['overrides']['records'] ?? ['global' => [], 'roles' => [], 'users' => []];
    $roles = $data['roles'] ?? [];
    $users = $data['users'] ?? [];
    $datasets = $data['datasets'] ?? [];
    $themes = $data['themes']['records'] ?? [];
    $themeTokens = $data['theme_tokens']['tokens'] ?? [];
    $defaultTheme = $data['default_theme'] ?? '';
    $themePolicy = $data['theme_policy'] ?? 'enabled';
    $translations = $data['translations'] ?? [];
    $translationTokens = $translations['tokens'] ?? [];
    $translationLocales = $translations['locales'] ?? [];
    $fallbackLocale = $translations['fallback_locale'] ?? 'en';
    $defaultTranslations = fg_default_translations_dataset();
    $defaultTranslationTokens = $defaultTranslations['tokens'] ?? [];
    $localePolicy = $data['locale_policy'] ?? 'enabled';
    $defaultLocaleSetting = $data['default_locale'] ?? $fallbackLocale;
    $pagesDataset = $data['pages'] ?? ['records' => [], 'next_id' => 1];
    $pageRecords = $pagesDataset['records'] ?? [];
    $message = $data['message'] ?? '';
    $errors = $data['errors'] ?? [];
    $activityRecords = $data['activity_records'] ?? [];
    $activityFilters = $data['activity_filters'] ?? ['dataset' => '', 'category' => '', 'action' => '', 'user' => ''];
    $activityLimit = (int) ($data['activity_limit'] ?? 50);
    $activityTotal = (int) ($data['activity_total'] ?? count($activityRecords));
    $activityDatasetLabels = $data['activity_dataset_labels'] ?? [];
    $activityCategories = $data['activity_categories'] ?? [];
    $activityActions = $data['activity_actions'] ?? [];

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

    $body .= '<section class="pages-manager">';
    $body .= '<h2>Page Management</h2>';
    $body .= '<p>Create and curate navigation-ready pages without editing code. Assign visibility, templates, and navigation stati';
    $body .= 'us per page.</p>';

    if (empty($pageRecords)) {
        $body .= '<p class="notice info">No pages published yet. Use the form below to create the first one.</p>';
    }

    foreach ($pageRecords as $page) {
        $pageId = (int) ($page['id'] ?? 0);
        $pageTitle = $page['title'] ?? 'Page';
        $pageSlug = $page['slug'] ?? '';
        $pageSummary = $page['summary'] ?? '';
        $pageContent = $page['content'] ?? '';
        $pageVisibility = $page['visibility'] ?? 'public';
        $pageFormat = $page['format'] ?? 'html';
        $pageTemplate = $page['template'] ?? 'standard';
        $pageRoles = array_map('strval', $page['allowed_roles'] ?? []);
        $pageNav = !empty($page['show_in_navigation']);

        $body .= '<article class="page-card" data-page="' . htmlspecialchars((string) $pageSlug) . '">';
        $body .= '<header><h3>' . htmlspecialchars($pageTitle) . '</h3>';
        $body .= '<p class="page-card-meta"><code>' . htmlspecialchars((string) $pageSlug) . '</code> · Visibility: ' . htmlspecialchars(ucfirst((string) $pageVisibility)) . '</p>';
        $body .= '</header>';
        $body .= '<form method="post" action="/setup.php" class="page-form">';
        $body .= '<input type="hidden" name="action" value="update_page">';
        $body .= '<input type="hidden" name="page_id" value="' . htmlspecialchars((string) $pageId) . '">';
        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value="' . htmlspecialchars($pageTitle) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Slug</span><span class="field-control"><input type="text" name="slug" value="' . htmlspecialchars((string) $pageSlug) . '"></span><span class="field-description">Used for the page URL.</span></label>';
        $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><input type="text" name="summary" value="' . htmlspecialchars($pageSummary) . '"></span></label>';
        $body .= '<label class="field"><span class="field-label">Format</span><span class="field-control"><select name="format">';
        foreach (['html' => 'HTML', 'text' => 'Plain text'] as $value => $labelOption) {
            $selected = $pageFormat === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($labelOption) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<label class="field"><span class="field-label">Template</span><span class="field-control"><input type="text" name="template" value="' . htmlspecialchars($pageTemplate) . '"></span><span class="field-description">Reference a template keyword for layout variations.</span></label>';
        $body .= '</div>';

        $body .= '<label class="field"><span class="field-label">Content</span><span class="field-control"><textarea name="content" rows="8">' . htmlspecialchars($pageContent) . '</textarea></span></label>';

        $body .= '<div class="field-grid">';
        $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
        foreach (['public' => 'Public', 'members' => 'Members', 'roles' => 'Selected roles'] as $value => $labelOption) {
            $selected = $pageVisibility === $value ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($labelOption) . '</option>';
        }
        $body .= '</select></span></label>';
        $body .= '<fieldset class="field"><legend>Roles permitted</legend><div class="field-checkbox-group">';
        foreach ($roles as $roleKey => $roleDescription) {
            $checked = in_array((string) $roleKey, $pageRoles, true) ? ' checked' : '';
            $body .= '<label><input type="checkbox" name="allowed_roles[]" value="' . htmlspecialchars((string) $roleKey) . '"' . $checked . '> ' . htmlspecialchars(ucfirst((string) $roleKey)) . '</label>';
        }
        $body .= '</div><p class="field-description">Only used when visibility is set to selected roles.</p></fieldset>';
        $checkedNav = $pageNav ? ' checked' : '';
        $body .= '<label class="field-toggle"><input type="checkbox" name="show_in_navigation" value="1"' . $checkedNav . '> Show in navigation</label>';
        $body .= '</div>';

        $body .= '<div class="action-row">';
        $body .= '<button type="submit" class="button primary">Save page</button>';
        $body .= '</div>';
        $body .= '</form>';

        $body .= '<form method="post" action="/setup.php" class="page-delete-form" onsubmit="return confirm(\'Delete this page?\');">';
        $body .= '<input type="hidden" name="action" value="delete_page">';
        $body .= '<input type="hidden" name="page_id" value="' . htmlspecialchars((string) $pageId) . '">';
        $body .= '<button type="submit" class="button danger">Delete page</button>';
        $body .= '</form>';

        $body .= '</article>';
    }

    $body .= '<article class="page-card create">';
    $body .= '<header><h3>Create new page</h3><p>Draft a new page with full control over visibility and placement.</p></header>';
    $body .= '<form method="post" action="/setup.php" class="page-form">';
    $body .= '<input type="hidden" name="action" value="create_page">';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Title</span><span class="field-control"><input type="text" name="title" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Slug</span><span class="field-control"><input type="text" name="slug" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Summary</span><span class="field-control"><input type="text" name="summary" value=""></span></label>';
    $body .= '<label class="field"><span class="field-label">Format</span><span class="field-control"><select name="format">';
    foreach (['html' => 'HTML', 'text' => 'Plain text'] as $value => $labelOption) {
        $body .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($labelOption) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<label class="field"><span class="field-label">Template</span><span class="field-control"><input type="text" name="template" value="standard"></span></label>';
    $body .= '</div>';
    $body .= '<label class="field"><span class="field-label">Content</span><span class="field-control"><textarea name="content" rows="6"></textarea></span></label>';
    $body .= '<div class="field-grid">';
    $body .= '<label class="field"><span class="field-label">Visibility</span><span class="field-control"><select name="visibility">';
    foreach (['public' => 'Public', 'members' => 'Members', 'roles' => 'Selected roles'] as $value => $labelOption) {
        $body .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($labelOption) . '</option>';
    }
    $body .= '</select></span></label>';
    $body .= '<fieldset class="field"><legend>Roles permitted</legend><div class="field-checkbox-group">';
    foreach ($roles as $roleKey => $roleDescription) {
        $body .= '<label><input type="checkbox" name="allowed_roles[]" value="' . htmlspecialchars((string) $roleKey) . '"> ' . htmlspecialchars(ucfirst((string) $roleKey)) . '</label>';
    }
    $body .= '</div><p class="field-description">Only active when restricting to selected roles.</p></fieldset>';
    $body .= '<label class="field-toggle"><input type="checkbox" name="show_in_navigation" value="1" checked> Show in navigation</label>';
    $body .= '</div>';
    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Create page</button>';
    $body .= '</div>';
    $body .= '</form>';
    $body .= '</article>';

    $body .= '</section>';

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
            $snapshots = $dataset['snapshots'] ?? [];
            $snapshotLimit = (int) ($dataset['snapshot_limit'] ?? 0);

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

            $body .= '<div class="snapshot-section">';
            $body .= '<h3>Snapshots</h3>';
            if ($snapshotLimit > 0) {
                $body .= '<p class="notice muted">Showing up to ' . htmlspecialchars((string) $snapshotLimit) . ' most recent captures for this dataset.</p>';
            }
            $body .= '<form method="post" action="/setup.php" class="snapshot-form">';
            $body .= '<input type="hidden" name="action" value="create_snapshot">';
            $body .= '<input type="hidden" name="dataset" value="' . htmlspecialchars($datasetName) . '">';
            $body .= '<label class="field compact">';
            $body .= '<span class="field-label">Snapshot label</span>';
            $body .= '<span class="field-control"><input type="text" name="snapshot_label" placeholder="Manual snapshot"></span>';
            $body .= '<span class="field-description">Provide a short label before capturing the current dataset state.</span>';
            $body .= '</label>';
            if ($editable) {
                $body .= '<button type="submit" class="button">Create snapshot</button>';
            } else {
                $body .= '<button type="submit" class="button" disabled>Create snapshot</button>';
            }
            $body .= '</form>';

            if (!empty($snapshots)) {
                $body .= '<div class="snapshot-list">';
                foreach ($snapshots as $snapshot) {
                    $snapshotId = (int) ($snapshot['id'] ?? 0);
                    $snapshotReason = $snapshot['reason'] ?? '';
                    $snapshotCreatedAt = $snapshot['created_at'] ?? '';
                    $snapshotUser = $snapshot['created_by'] ?? '';
                    $snapshotPreview = $snapshot['preview'] ?? '';
                    $snapshotFormat = strtoupper($snapshot['format'] ?? 'json');

                    $body .= '<article class="snapshot-card" data-snapshot="' . htmlspecialchars((string) $snapshotId) . '">';
                    $body .= '<header>';
                    $body .= '<span class="snapshot-reason">' . htmlspecialchars($snapshotReason === '' ? 'Snapshot #' . $snapshotId : $snapshotReason) . '</span>';
                    $body .= '<span class="snapshot-meta">';
                    if ($snapshotCreatedAt !== '') {
                        $body .= '<span class="snapshot-chip">' . htmlspecialchars($snapshotCreatedAt) . '</span>';
                    }
                    $body .= '<span class="snapshot-chip">Format: ' . htmlspecialchars($snapshotFormat) . '</span>';
                    if ($snapshotUser !== '') {
                        $body .= '<span class="snapshot-chip">Captured by ' . htmlspecialchars($snapshotUser) . '</span>';
                    }
                    $body .= '</span>';
                    $body .= '</header>';
                    $body .= '<div class="snapshot-preview">';
                    $body .= '<textarea rows="6" readonly>' . htmlspecialchars($snapshotPreview) . '</textarea>';
                    $body .= '</div>';
                    $body .= '<div class="snapshot-actions">';
                    $body .= '<form method="post" action="/setup.php" class="inline-form">';
                    $body .= '<input type="hidden" name="action" value="restore_snapshot">';
                    $body .= '<input type="hidden" name="dataset" value="' . htmlspecialchars($datasetName) . '">';
                    $body .= '<input type="hidden" name="snapshot_id" value="' . htmlspecialchars((string) $snapshotId) . '">';
                    if ($editable) {
                        $body .= '<button type="submit" class="button primary">Restore</button>';
                    } else {
                        $body .= '<button type="submit" class="button" disabled>Restore</button>';
                    }
                    $body .= '</form>';
                    $body .= '<form method="post" action="/setup.php" class="inline-form">';
                    $body .= '<input type="hidden" name="action" value="delete_snapshot">';
                    $body .= '<input type="hidden" name="dataset" value="' . htmlspecialchars($datasetName) . '">';
                    $body .= '<input type="hidden" name="snapshot_id" value="' . htmlspecialchars((string) $snapshotId) . '">';
                    if ($editable) {
                        $body .= '<button type="submit" class="button danger">Delete</button>';
                    } else {
                        $body .= '<button type="submit" class="button" disabled>Delete</button>';
                    }
                    $body .= '</form>';
                    $body .= '</div>';
                    $body .= '</article>';
                }
                $body .= '</div>';
            } else {
                $body .= '<p class="notice muted">No snapshots have been recorded yet.</p>';
            }
            $body .= '</div>';

            $body .= '</div>';
            $body .= '</details>';
        }

        $body .= '</section>';
    }

    $datasetOptions = $activityDatasetLabels;
    if (!is_array($datasetOptions)) {
        $datasetOptions = [];
    } else {
        asort($datasetOptions);
    }

    if (!is_array($activityCategories)) {
        $activityCategories = [];
    }

    if (!is_array($activityActions)) {
        $activityActions = [];
    }

    $body .= '<section class="activity-log" id="activity-log">';
    $body .= '<h2>Activity log</h2>';
    $body .= '<p>Inspect the audit trail for dataset saves, snapshot operations, and administrative actions without leaving the browser.</p>';
    $body .= '<form method="get" action="/setup.php" class="activity-filter-form">';
    $body .= '<div class="activity-filter-grid">';
    $body .= '<label class="field"><span class="field-label">Dataset</span><span class="field-control"><select name="log_dataset">';
    $body .= '<option value="">All datasets</option>';
    foreach ($datasetOptions as $datasetKey => $datasetLabel) {
        $selected = ((string) ($activityFilters['dataset'] ?? '') === (string) $datasetKey) ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars((string) $datasetKey) . '"' . $selected . '>' . htmlspecialchars($datasetLabel) . '</option>';
    }
    $body .= '</select></span></label>';

    $body .= '<label class="field"><span class="field-label">Category</span><span class="field-control"><select name="log_category">';
    $body .= '<option value="">All categories</option>';
    foreach ($activityCategories as $categoryValue) {
        $categoryValue = (string) $categoryValue;
        $selected = ((string) ($activityFilters['category'] ?? '') === $categoryValue) ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($categoryValue) . '"' . $selected . '>' . htmlspecialchars(ucfirst($categoryValue)) . '</option>';
    }
    $body .= '</select></span></label>';

    $body .= '<label class="field"><span class="field-label">Action</span><span class="field-control"><select name="log_action">';
    $body .= '<option value="">All actions</option>';
    foreach ($activityActions as $actionValue) {
        $actionValue = (string) $actionValue;
        $selected = ((string) ($activityFilters['action'] ?? '') === $actionValue) ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($actionValue) . '"' . $selected . '>' . htmlspecialchars(ucfirst($actionValue)) . '</option>';
    }
    $body .= '</select></span></label>';

    $body .= '<label class="field"><span class="field-label">User filter</span><span class="field-control"><input type="text" name="log_user" value="' . htmlspecialchars((string) ($activityFilters['user'] ?? '')) . '" placeholder="username, #id, role"></span><span class="field-description">Matches usernames, roles, and numeric identifiers.</span></label>';

    $body .= '<label class="field compact"><span class="field-label">Show</span><span class="field-control"><input type="number" name="log_limit" min="5" max="200" value="' . htmlspecialchars((string) $activityLimit) . '"></span><span class="field-description">Maximum entries to display.</span></label>';
    $body .= '</div>';
    $body .= '<div class="action-row">';
    $body .= '<button type="submit" class="button primary">Apply filters</button>';
    $body .= '<a class="button" href="/setup.php#activity-log">Reset</a>';
    $body .= '</div>';
    $body .= '</form>';

    $body .= '<p class="activity-summary">Showing ' . htmlspecialchars((string) count($activityRecords)) . ' of ' . htmlspecialchars((string) $activityTotal) . ' recorded events.</p>';

    if (empty($activityRecords)) {
        $body .= '<p class="notice muted">No activity recorded for the selected filters yet.</p>';
    } else {
        $body .= '<div class="activity-entries">';
        foreach ($activityRecords as $entry) {
            $categoryLabel = (string) ($entry['category'] ?? '');
            $actionLabel = (string) ($entry['action'] ?? '');
            $datasetLabel = (string) ($entry['dataset_label'] ?? '');
            $datasetKey = (string) ($entry['dataset'] ?? '');
            $summaryParts = [];
            if ($categoryLabel !== '') {
                $summaryParts[] = ucfirst($categoryLabel);
            }
            if ($actionLabel !== '') {
                $summaryParts[] = ucfirst($actionLabel);
            }
            if ($datasetLabel !== '') {
                $summaryParts[] = $datasetLabel;
            }
            $summaryText = implode(' · ', $summaryParts);
            if ($summaryText === '') {
                $summaryText = 'Activity entry';
            }

            $metaParts = [];
            $metaParts[] = '#' . (int) ($entry['id'] ?? 0);
            if (!empty($entry['created_at_display'])) {
                $metaParts[] = (string) $entry['created_at_display'];
            }
            if (!empty($entry['performed_by_display'])) {
                $metaParts[] = (string) $entry['performed_by_display'];
            }

            $body .= '<details class="activity-entry">';
            $body .= '<summary><span class="activity-summary-main">' . htmlspecialchars($summaryText) . '</span><span class="activity-summary-meta">' . htmlspecialchars(implode(' · ', $metaParts)) . '</span></summary>';
            $body .= '<div class="activity-entry-body">';
            $body .= '<dl class="activity-entry-grid">';
            $body .= '<dt>Event ID</dt><dd>#' . htmlspecialchars((string) ($entry['id'] ?? 0)) . '</dd>';
            if (!empty($entry['created_at_display'])) {
                $body .= '<dt>Recorded at</dt><dd>' . htmlspecialchars((string) $entry['created_at_display']) . ' UTC</dd>';
            }
            if (!empty($entry['trigger'])) {
                $body .= '<dt>Trigger</dt><dd>' . htmlspecialchars((string) $entry['trigger']) . '</dd>';
            }
            if (!empty($entry['performed_by_display'])) {
                $body .= '<dt>Actor</dt><dd>' . htmlspecialchars((string) $entry['performed_by_display']) . '</dd>';
            }
            if ($datasetLabel !== '' || $datasetKey !== '') {
                $body .= '<dt>Dataset</dt><dd>' . htmlspecialchars($datasetLabel === '' ? $datasetKey : $datasetLabel);
                if ($datasetLabel !== '' && $datasetKey !== '' && $datasetKey !== $datasetLabel) {
                    $body .= ' <span class="activity-dataset-key">(' . htmlspecialchars($datasetKey) . ')</span>';
                }
                $body .= '</dd>';
            }
            if (!empty($entry['ip_address'])) {
                $body .= '<dt>IP address</dt><dd>' . htmlspecialchars((string) $entry['ip_address']) . '</dd>';
            }
            if (!empty($entry['user_agent_display'])) {
                $body .= '<dt>User agent</dt><dd>' . htmlspecialchars((string) $entry['user_agent_display']);
                if (!empty($entry['user_agent']) && (string) $entry['user_agent'] !== (string) $entry['user_agent_display']) {
                    $body .= ' <span class="activity-truncate-note">(truncated)</span>';
                }
                $body .= '</dd>';
            }
            $body .= '</dl>';

            if (!empty($entry['details_json'])) {
                $body .= '<div class="activity-json"><h4>Details</h4><pre>' . htmlspecialchars((string) $entry['details_json']) . '</pre></div>';
            }

            if (!empty($entry['context_json'])) {
                $body .= '<div class="activity-json"><h4>Context</h4><pre>' . htmlspecialchars((string) $entry['context_json']) . '</pre></div>';
            }

            $body .= '</div>';
            $body .= '</details>';
        }
        $body .= '</div>';
    }

    $body .= '</section>';

    if (!empty($themes)) {
        $body .= '<section class="theme-manager">';
        $body .= '<h2>Theme management</h2>';
        $body .= '<p>Author palette presets, tune CSS variables, and decide which theme greets new members.</p>';
        if ($themePolicy === 'disabled') {
            $body .= '<p class="notice warning">Member personalisation is currently disabled via the Theme Personalisation Policy setting.</p>';
        }
        $body .= '<div class="theme-grid">';
        foreach ($themes as $themeKey => $themeDefinition) {
            $label = $themeDefinition['label'] ?? $themeKey;
            $description = $themeDefinition['description'] ?? '';
            $tokensForTheme = $themeDefinition['tokens'] ?? [];
            $encodedTokens = htmlspecialchars(json_encode($tokensForTheme, JSON_UNESCAPED_SLASHES), ENT_QUOTES);
            $body .= '<article class="theme-card" data-theme-key="' . htmlspecialchars($themeKey) . '">';
            $body .= '<header><h3>' . htmlspecialchars($label) . '</h3>';
            if ($themeKey === $defaultTheme) {
                $body .= '<span class="theme-badge">Default</span>';
            }
            $body .= '</header>';
            if ($description !== '') {
                $body .= '<p class="theme-description">' . htmlspecialchars($description) . '</p>';
            }
            $body .= '<form method="post" action="/setup.php" class="theme-form" data-theme-preview data-theme-values="' . $encodedTokens . '">';
            $body .= '<input type="hidden" name="action" value="update_theme">';
            $body .= '<input type="hidden" name="theme_key" value="' . htmlspecialchars($themeKey) . '">';
            $body .= '<label class="field"><span class="field-label">Display label</span><span class="field-control"><input type="text" name="label" value="' . htmlspecialchars($label) . '"></span><span class="field-description">Shown to administrators and members when choosing a preset.</span></label>';
            $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="2">' . htmlspecialchars($description) . '</textarea></span></label>';
            $body .= '<div class="theme-token-grid">';
            foreach ($themeTokens as $tokenKey => $definition) {
                $tokenLabel = $definition['label'] ?? ucfirst($tokenKey);
                $tokenDescription = $definition['description'] ?? '';
                $cssVariable = $definition['css_variable'] ?? ('--fg-' . str_replace('_', '-', $tokenKey));
                $type = $definition['type'] ?? 'text';
                $value = $tokensForTheme[$tokenKey] ?? ($definition['default'] ?? '');
                $body .= '<label class="field" data-theme-token="' . htmlspecialchars($tokenKey) . '">';
                $body .= '<span class="field-label">' . htmlspecialchars($tokenLabel) . '</span>';
                if ($type === 'color') {
                    $body .= '<span class="field-control"><input type="color" name="tokens[' . htmlspecialchars($tokenKey) . ']" value="' . htmlspecialchars($value) . '" data-theme-token-input data-css-variable="' . htmlspecialchars($cssVariable) . '"></span>';
                } else {
                    $body .= '<span class="field-control"><input type="text" name="tokens[' . htmlspecialchars($tokenKey) . ']" value="' . htmlspecialchars($value) . '" data-theme-token-input data-css-variable="' . htmlspecialchars($cssVariable) . '"></span>';
                }
                $body .= '<span class="field-description">' . htmlspecialchars($tokenDescription) . ' · ' . htmlspecialchars($cssVariable) . '</span>';
                $body .= '</label>';
            }
            $body .= '</div>';
            $body .= '<div class="theme-preview" data-theme-preview-target>';
            $body .= '<div class="theme-preview-header">Preview</div>';
            $body .= '<p class="theme-preview-body">Interface text previews with the current palette.</p>';
            $body .= '<div class="accent">Accent example</div>';
            $body .= '<div class="swatch-row">';
            $body .= '<span class="swatch positive">Positive</span>';
            $body .= '<span class="swatch warning">Warning</span>';
            $body .= '<span class="swatch negative">Critical</span>';
            $body .= '</div>';
            $body .= '</div>';
            $body .= '<div class="action-row">';
            $body .= '<button type="submit" class="button primary">Save theme</button>';
            $body .= '<button type="button" class="button" data-theme-reset>Reapply stored values</button>';
            $body .= '</div>';
            $body .= '</form>';
            $body .= '<div class="theme-card-actions">';
            if ($themeKey !== $defaultTheme) {
                $body .= '<form method="post" action="/setup.php" class="inline-form">';
                $body .= '<input type="hidden" name="action" value="set_default_theme">';
                $body .= '<input type="hidden" name="theme_key" value="' . htmlspecialchars($themeKey) . '">';
                $body .= '<button type="submit" class="button">Set as default</button>';
                $body .= '</form>';
            } else {
                $body .= '<p class="theme-current">This theme is the default experience.</p>';
            }
            if (count($themes) > 1 && $themeKey !== $defaultTheme) {
                $body .= '<form method="post" action="/setup.php" class="inline-form">';
                $body .= '<input type="hidden" name="action" value="delete_theme">';
                $body .= '<input type="hidden" name="theme_key" value="' . htmlspecialchars($themeKey) . '">';
                $body .= '<button type="submit" class="button danger">Delete theme</button>';
                $body .= '</form>';
            }
            $body .= '</div>';
            $body .= '</article>';
        }
        $body .= '</div>';

        $defaultTokenValues = [];
        foreach ($themeTokens as $tokenKey => $definition) {
            $defaultTokenValues[$tokenKey] = $definition['default'] ?? '';
        }
        $encodedDefaults = htmlspecialchars(json_encode($defaultTokenValues, JSON_UNESCAPED_SLASHES), ENT_QUOTES);

        $body .= '<details class="theme-create" open>';
        $body .= '<summary>Create new theme</summary>';
        $body .= '<form method="post" action="/setup.php" class="theme-form" data-theme-preview data-theme-values="' . $encodedDefaults . '">';
        $body .= '<input type="hidden" name="action" value="create_theme">';
        $body .= '<label class="field"><span class="field-label">Theme key</span><span class="field-control"><input type="text" name="theme_key" pattern="[a-z0-9_-]+" required></span><span class="field-description">Use lowercase letters, numbers, hyphens, or underscores.</span></label>';
        $body .= '<label class="field"><span class="field-label">Display label</span><span class="field-control"><input type="text" name="label" placeholder="Aurora"></span></label>';
        $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="description" rows="2" placeholder="Describe when to use this palette."></textarea></span></label>';
        $body .= '<div class="theme-token-grid">';
        foreach ($themeTokens as $tokenKey => $definition) {
            $tokenLabel = $definition['label'] ?? ucfirst($tokenKey);
            $cssVariable = $definition['css_variable'] ?? ('--fg-' . str_replace('_', '-', $tokenKey));
            $type = $definition['type'] ?? 'text';
            $value = $definition['default'] ?? '';
            $body .= '<label class="field" data-theme-token="' . htmlspecialchars($tokenKey) . '">';
            $body .= '<span class="field-label">' . htmlspecialchars($tokenLabel) . '</span>';
            if ($type === 'color') {
                $body .= '<span class="field-control"><input type="color" name="tokens[' . htmlspecialchars($tokenKey) . ']" value="' . htmlspecialchars($value) . '" data-theme-token-input data-css-variable="' . htmlspecialchars($cssVariable) . '"></span>';
            } else {
                $body .= '<span class="field-control"><input type="text" name="tokens[' . htmlspecialchars($tokenKey) . ']" value="' . htmlspecialchars($value) . '" data-theme-token-input data-css-variable="' . htmlspecialchars($cssVariable) . '"></span>';
            }
            $body .= '<span class="field-description">CSS variable ' . htmlspecialchars($cssVariable) . '</span>';
            $body .= '</label>';
        }
        $body .= '</div>';
        $body .= '<div class="theme-preview" data-theme-preview-target>';
        $body .= '<div class="theme-preview-header">Preview</div>';
        $body .= '<p class="theme-preview-body">Adjust the tokens to craft a new experience.</p>';
        $body .= '<div class="accent">Accent example</div>';
        $body .= '<div class="swatch-row">';
        $body .= '<span class="swatch positive">Positive</span>';
        $body .= '<span class="swatch warning">Warning</span>';
        $body .= '<span class="swatch negative">Critical</span>';
        $body .= '</div>';
        $body .= '</div>';
        $body .= '<div class="action-row">';
        $body .= '<button type="submit" class="button primary">Create theme</button>';
        $body .= '<button type="button" class="button" data-theme-reset>Reset to defaults</button>';
        $body .= '</div>';
        $body .= '</form>';
        $body .= '</details>';

        $body .= '</section>';
    }

    if (!empty($translationLocales) || !empty($translationTokens)) {
        $body .= '<section class="translations-manager">';
        $localeCount = count($translationLocales);
        $tokenCount = count($translationTokens);
        $body .= '<h2>Locale management</h2>';
        $body .= '<p>Adjust interface strings per locale and control which language acts as the fallback. Delegated settings govern the default locale presented to new accounts.</p>';
        $body .= '<p class="notice muted">Fallback locale: <strong>' . htmlspecialchars((string) $fallbackLocale) . '</strong> · Default locale setting: <strong>' . htmlspecialchars((string) $defaultLocaleSetting) . '</strong> · Policy: <strong>' . htmlspecialchars((string) $localePolicy) . '</strong> · Tokens: <strong>' . htmlspecialchars((string) $tokenCount) . '</strong> · Locales: <strong>' . htmlspecialchars((string) $localeCount) . '</strong></p>';

        $body .= '<div class="translation-token-collection">';
        $body .= '<h3>Translation tokens</h3>';
        $body .= '<p class="translation-summary">Define the reusable strings referenced across the interface. Tokens let you grow Filegate with new modules without touching PHP by pairing each feature with its own phrase.</p>';
        $body .= '<details class="translation-create translation-token-create">';
        $body .= '<summary>Create translation token</summary>';
        $body .= '<form method="post" action="/setup.php" class="translation-form create">';
        $body .= '<input type="hidden" name="action" value="translation_create_token">';
        $body .= '<label class="field"><span class="field-label">Token key</span><span class="field-control"><input type="text" name="token_key" pattern="[a-z0-9._-]+" required></span><span class="field-description">Use dot-separated namespaces (for example, <code>composer.publish.cta</code>).</span></label>';
        $body .= '<label class="field"><span class="field-label">Display label</span><span class="field-control"><input type="text" name="token_label" placeholder="Composer · Publish CTA"></span><span class="field-description">Shown to administrators and translators.</span></label>';
        $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="token_description" rows="2" placeholder="Explain where this string appears."></textarea></span></label>';
        $body .= '<div class="action-row"><button type="submit" class="button primary">Create token</button></div>';
        $body .= '</form>';
        $body .= '</details>';

        if (!empty($translationTokens)) {
            foreach ($translationTokens as $tokenKey => $tokenMeta) {
                $label = $tokenMeta['label'] ?? $tokenKey;
                $description = $tokenMeta['description'] ?? '';
                $coverageTotal = max(1, $localeCount);
                $coverageDefined = 0;
                $missingLocales = [];
                foreach ($translationLocales as $localeKey => $definition) {
                    $value = $definition['strings'][$tokenKey] ?? '';
                    if ((string) $value !== '') {
                        $coverageDefined++;
                    } else {
                        $missingLocales[] = $definition['label'] ?? $localeKey;
                    }
                }
                $isSeededToken = isset($defaultTranslationTokens[$tokenKey]);
                $body .= '<article class="translation-token-card" data-token="' . htmlspecialchars((string) $tokenKey) . '">';
                $body .= '<header><h4>' . htmlspecialchars((string) $label) . '</h4>';
                $badges = [];
                $badges[] = $coverageDefined . ' / ' . $coverageTotal . ' locales';
                $badges[] = $isSeededToken ? 'Seeded' : 'Custom';
                if (!empty($badges)) {
                    $body .= '<span class="translation-badges">';
                    foreach ($badges as $badge) {
                        $body .= '<span class="translation-badge">' . htmlspecialchars((string) $badge) . '</span>';
                    }
                    $body .= '</span>';
                }
                $body .= '</header>';
                $body .= '<p class="translation-token-key"><code>' . htmlspecialchars((string) $tokenKey) . '</code></p>';
                if ($description !== '') {
                    $body .= '<p class="translation-token-description">' . htmlspecialchars((string) $description) . '</p>';
                }
                if (!empty($missingLocales)) {
                    $body .= '<p class="translation-token-missing">Missing strings: ' . htmlspecialchars(implode(', ', array_map('strval', $missingLocales))) . '</p>';
                } else {
                    $body .= '<p class="translation-token-covered">Defined for every locale.</p>';
                }
                $body .= '<form method="post" action="/setup.php" class="translation-token-form">';
                $body .= '<input type="hidden" name="action" value="translation_update_token">';
                $body .= '<input type="hidden" name="token_key" value="' . htmlspecialchars((string) $tokenKey) . '">';
                $body .= '<label class="field"><span class="field-label">Display label</span><span class="field-control"><input type="text" name="token_label" value="' . htmlspecialchars((string) $label) . '"></span></label>';
                $body .= '<label class="field"><span class="field-label">Description</span><span class="field-control"><textarea name="token_description" rows="2">' . htmlspecialchars((string) $description) . '</textarea></span><span class="field-description">Help translators understand how the token is used.</span></label>';
                $body .= '<label class="field"><span class="field-label">Fill value</span><span class="field-control"><textarea name="fill_value" rows="2" placeholder="Optional string to cascade to locales"></textarea></span><span class="field-description">Use with the fill mode below to auto-populate locale strings when saving.</span></label>';
                $body .= '<label class="field"><span class="field-label">Fill mode</span><span class="field-control"><select name="fill_mode">';
                $body .= '<option value="">Do not apply fill value</option>';
                $body .= '<option value="missing">Fill missing locale strings only</option>';
                $body .= '<option value="all">Replace every locale string</option>';
                $body .= '</select></span></label>';
                $body .= '<div class="action-row"><button type="submit" class="button primary">Save token</button></div>';
                $body .= '</form>';
                $body .= '<div class="translation-token-actions">';
                if ($isSeededToken) {
                    $body .= '<p class="translation-token-note">Seeded tokens remain available to guarantee baseline navigation. Override their strings per locale to customise Filegate.</p>';
                } else {
                    $body .= '<form method="post" action="/setup.php" class="inline-form">';
                    $body .= '<input type="hidden" name="action" value="translation_delete_token">';
                    $body .= '<input type="hidden" name="token_key" value="' . htmlspecialchars((string) $tokenKey) . '">';
                    $body .= '<button type="submit" class="button danger">Delete token</button>';
                    $body .= '</form>';
                }
                $body .= '</div>';
                $body .= '</article>';
            }
        } else {
            $body .= '<p class="notice muted">No translation tokens are registered yet. Create one to begin localising new interface areas.</p>';
        }
        $body .= '</div>';

        if (!empty($translationLocales)) {
            $body .= '<h3>Locales</h3>';
            $body .= '<details class="translation-create">';
            $body .= '<summary>Create locale</summary>';
            $body .= '<form method="post" action="/setup.php" class="translation-form create">';
            $body .= '<input type="hidden" name="action" value="translation_create_locale">';
            $body .= '<label class="field"><span class="field-label">Locale key</span><span class="field-control"><input type="text" name="locale_key" pattern="[a-z0-9_-]+" required></span><span class="field-description">Use lowercase letters, numbers, hyphens, or underscores.</span></label>';
            $body .= '<label class="field"><span class="field-label">Display label</span><span class="field-control"><input type="text" name="locale_label" placeholder="English (Canada)"></span></label>';
            $body .= '<label class="field"><span class="field-label">Copy strings from</span><span class="field-control"><select name="copy_from">';
            foreach ($translationLocales as $key => $definition) {
                $label = $definition['label'] ?? $key;
                $body .= '<option value="' . htmlspecialchars((string) $key) . '">' . htmlspecialchars((string) $label) . '</option>';
            }
            $body .= '</select></span></label>';
            $body .= '<div class="action-row"><button type="submit" class="button primary">Create locale</button></div>';
            $body .= '</form>';
            $body .= '</details>';

            foreach ($translationLocales as $localeKey => $definition) {
                $label = $definition['label'] ?? $localeKey;
                $strings = $definition['strings'] ?? [];
                $isFallback = ($localeKey === $fallbackLocale);
                $isDefault = ((string) $defaultLocaleSetting === (string) $localeKey);

                $body .= '<article class="translation-card" data-locale="' . htmlspecialchars((string) $localeKey) . '">';
                $body .= '<header><h3>' . htmlspecialchars((string) $label) . '</h3>';
                $badges = [];
                if ($isFallback) {
                    $badges[] = 'Fallback';
                }
                if ($isDefault) {
                    $badges[] = 'Default setting';
                }
                if (!empty($badges)) {
                    $body .= '<span class="translation-badges">';
                    foreach ($badges as $badge) {
                        $body .= '<span class="translation-badge">' . htmlspecialchars($badge) . '</span>';
                    }
                    $body .= '</span>';
                }
                $body .= '</header>';

                $body .= '<form method="post" action="/setup.php" class="translation-form">';
                $body .= '<input type="hidden" name="action" value="translation_save_locale">';
                $body .= '<input type="hidden" name="locale" value="' . htmlspecialchars((string) $localeKey) . '">';
                $body .= '<label class="field"><span class="field-label">Display label</span><span class="field-control"><input type="text" name="locale_label" value="' . htmlspecialchars((string) $label) . '"></span></label>';
                $body .= '<div class="translation-token-grid">';
                foreach ($translationTokens as $tokenKey => $tokenMeta) {
                    $tokenLabel = $tokenMeta['label'] ?? $tokenKey;
                    $tokenDescription = $tokenMeta['description'] ?? '';
                    $value = $strings[$tokenKey] ?? '';
                    $body .= '<label class="field" data-token="' . htmlspecialchars((string) $tokenKey) . '">';
                    $body .= '<span class="field-label">' . htmlspecialchars((string) $tokenLabel) . '</span>';
                    $body .= '<span class="field-control"><textarea name="strings[' . htmlspecialchars((string) $tokenKey) . ']" rows="2">' . htmlspecialchars((string) $value) . '</textarea></span>';
                    if ($tokenDescription !== '') {
                        $body .= '<span class="field-description">' . htmlspecialchars((string) $tokenDescription) . '</span>';
                    }
                    $body .= '</label>';
                }
                $body .= '</div>';
                $body .= '<div class="action-row"><button type="submit" class="button primary">Save translations</button></div>';
                $body .= '</form>';

                $body .= '<div class="translation-secondary-actions">';
                if (!$isFallback) {
                    $body .= '<form method="post" action="/setup.php" class="inline-form">';
                    $body .= '<input type="hidden" name="action" value="translation_set_fallback">';
                    $body .= '<input type="hidden" name="locale" value="' . htmlspecialchars((string) $localeKey) . '">';
                    $body .= '<button type="submit" class="button">Set as fallback</button>';
                    $body .= '</form>';
                }
                if (count($translationLocales) > 1) {
                    $disabled = $isFallback ? ' disabled' : '';
                    $body .= '<form method="post" action="/setup.php" class="inline-form">';
                    $body .= '<input type="hidden" name="action" value="translation_delete_locale">';
                    $body .= '<input type="hidden" name="locale" value="' . htmlspecialchars((string) $localeKey) . '">';
                    $body .= '<button type="submit" class="button danger"' . $disabled . '>Delete locale</button>';
                    $body .= '</form>';
                }
                $body .= '</div>';

                $body .= '</article>';
            }
        }

        $body .= '</section>';
    }

    fg_render_layout('Asset Setup', $body);
}
