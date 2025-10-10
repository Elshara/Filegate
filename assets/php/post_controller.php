<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/add_post.php';
require_once __DIR__ . '/update_post.php';
require_once __DIR__ . '/find_post_by_id.php';
require_once __DIR__ . '/parse_collaborators.php';
require_once __DIR__ . '/render_layout.php';
require_once __DIR__ . '/store_upload.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/load_template_options.php';
require_once __DIR__ . '/load_editor_options.php';
require_once __DIR__ . '/load_notification_channels.php';
require_once __DIR__ . '/update_upload_meta.php';
require_once __DIR__ . '/guard_asset.php';
require_once __DIR__ . '/find_content_module.php';
require_once __DIR__ . '/normalize_content_module.php';

function fg_public_post_controller(): void
{
    fg_bootstrap();
    $user = fg_require_login();
    fg_guard_asset('assets/php/post_controller.php', [
        'role' => $user['role'] ?? null,
        'user_id' => $user['id'] ?? null,
    ]);
    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
    $post = $post_id ? fg_find_post_by_id($post_id) : null;
    $module_key = isset($_GET['module']) ? (string) $_GET['module'] : '';
    $moduleOptions = [
        'viewer' => $user,
        'enforce_visibility' => true,
        'statuses' => ['active'],
    ];
    $module = $module_key !== '' ? fg_find_content_module($module_key, 'posts', $moduleOptions) : null;
    $max_uploads = (int) fg_get_setting('upload_limits', 5);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $variables_input = $_POST['variables'] ?? '';
        $variables = [];
        if (is_array($variables_input)) {
            $variables = $variables_input;
        } elseif (is_string($variables_input) && trim($variables_input) !== '') {
            $decoded = json_decode($variables_input, true);
            if (is_array($decoded)) {
                $variables = $decoded;
            }
        }

        $tags_input = $_POST['tags'] ?? '';
        $tags = is_array($tags_input) ? $tags_input : array_filter(array_map('trim', explode(',', (string) $tags_input)));

        $display_options = [
            'show_statistics' => isset($_POST['display_statistics']),
            'show_embeds' => isset($_POST['display_embeds']),
        ];

        $notification_channels = $_POST['notification_channels'] ?? [];
        if (!is_array($notification_channels)) {
            $notification_channels = [$notification_channels];
        }

        $payload = [
            'author_id' => $user['id'],
            'content' => $_POST['content'] ?? '',
            'summary' => $_POST['summary'] ?? '',
            'custom_type' => trim($_POST['custom_type'] ?? ''),
            'conversation_style' => $_POST['conversation_style'] ?? 'standard',
            'privacy' => $_POST['privacy'] ?? 'public',
            'collaborators' => fg_parse_collaborators($_POST['collaborators'] ?? ''),
            'template' => $_POST['template'] ?? 'standard',
            'format_options' => ['content_format' => $_POST['content_format'] ?? 'html'],
            'display_options' => $display_options,
            'notification_template' => $_POST['notification_template'] ?? 'post_update',
            'notification_channels' => array_values(array_unique(array_filter(array_map('trim', $notification_channels)))),
            'tags' => $tags,
            'variables' => $variables,
        ];

        $content_module_key = isset($_POST['content_module_key']) ? (string) $_POST['content_module_key'] : '';
        $content_module_snapshot = $_POST['content_module_snapshot'] ?? '';
        $module_definition = null;
        if (is_string($content_module_snapshot) && trim($content_module_snapshot) !== '') {
            $decoded = json_decode($content_module_snapshot, true);
            if (is_array($decoded)) {
                $module_definition = fg_normalize_content_module_definition($decoded);
            }
        }
        if ($content_module_key !== '') {
            $findOptions = [
                'statuses' => ['active', 'draft', 'archived'],
            ];
            if (empty($_POST['post_id'])) {
                $findOptions['viewer'] = $user;
                $findOptions['enforce_visibility'] = true;
                $findOptions['statuses'] = ['active'];
            }
            $loaded_module = fg_find_content_module(
                $content_module_key,
                $module_definition['dataset'] ?? 'posts',
                $findOptions
            );
            if ($loaded_module !== null) {
                $module_definition = $loaded_module;
            }
        }
        if ($module_definition !== null) {
            $fields_input = $_POST['content_module_fields'] ?? [];
            if (!is_array($fields_input)) {
                $fields_input = [];
            }
            $normalized_fields = [];
            foreach ($module_definition['fields'] as $field) {
                if (!is_array($field) || empty($field['key'])) {
                    continue;
                }
                $key = (string) $field['key'];
                $value = $fields_input[$key] ?? '';
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
                $field['value'] = trim((string) $value);
                $normalized_fields[] = $field;
            }
            $module_definition['fields'] = $normalized_fields;

            $stage_options = $module_definition['wizard_steps'] ?? [];
            $stage_input = trim((string) ($_POST['content_module_stage'] ?? ''));
            if ($stage_input === '' && !empty($stage_options)) {
                $stage_input = $stage_options[0];
            }
            if ($stage_input !== '' && !empty($stage_options)) {
                $matched = false;
                foreach ($stage_options as $stage_option) {
                    if (strcasecmp((string) $stage_option, $stage_input) === 0) {
                        $stage_input = (string) $stage_option;
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    $stage_input = $stage_options[0];
                }
            }
            $module_definition['stage'] = $stage_input;

            $payload['content_module'] = $module_definition;
        }

        $existing_attachments = $post['attachments'] ?? [];
        $remove = $_POST['remove_attachments'] ?? [];
        if (!is_array($remove)) {
            $remove = [$remove];
        }
        $remove_ids = array_map('intval', $remove);
        $filtered = [];
        foreach ($existing_attachments as $attachment) {
            if (!in_array((int) ($attachment['id'] ?? 0), $remove_ids, true)) {
                $filtered[] = $attachment;
            }
        }

        $uploads = [];
        if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
            $files = $_FILES['attachments'];
            $count = count($files['name']);
            for ($i = 0; $i < $count && $i < $max_uploads; $i++) {
                $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                if ((int) $error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $file = [
                    'name' => $files['name'][$i] ?? null,
                    'type' => $files['type'][$i] ?? null,
                    'tmp_name' => $files['tmp_name'][$i] ?? null,
                    'error' => $error,
                    'size' => $files['size'][$i] ?? 0,
                ];
                $stored = fg_store_upload($file, ['author_id' => $user['id'], 'post_id' => $post_id ?: null]);
                if ($stored !== null) {
                    $uploads[] = $stored;
                }
            }
        }

        $payload['attachments'] = array_merge($filtered, $uploads);

        if (isset($_POST['post_id'])) {
            $payload['id'] = (int) $_POST['post_id'];
            $existing = fg_find_post_by_id($payload['id']);
            if ($existing && ((int) $existing['author_id'] === (int) $user['id'] || in_array($user['username'], $existing['collaborators'] ?? [], true))) {
                $updated = fg_update_post(array_merge($existing, $payload));
                if ($updated) {
                    foreach ($updated['attachments'] ?? [] as $attachment) {
                        if (isset($attachment['id'])) {
                            fg_update_upload_meta((int) $attachment['id'], ['post_id' => $updated['id']]);
                        }
                    }
                    foreach ($remove_ids as $removed_id) {
                        fg_update_upload_meta((int) $removed_id, ['post_id' => null, 'removed' => true]);
                    }
                    header('Location: /index.php#post-' . $payload['id']);
                    exit;
                }
            }
        } else {
            $created = fg_add_post($payload);
            foreach ($created['attachments'] ?? [] as $attachment) {
                if (isset($attachment['id'])) {
                    fg_update_upload_meta((int) $attachment['id'], ['post_id' => $created['id']]);
                }
            }
            header('Location: /index.php#post-' . $created['id']);
            exit;
        }
    }

    if ($post && ((int) $post['author_id'] === (int) $user['id'] || in_array($user['username'], $post['collaborators'] ?? [], true))) {
        $templates = fg_load_template_options();
        $template_select = '';
        foreach ($templates as $template) {
            $value = $template['name'] ?? '';
            if ($value === '') {
                continue;
            }
            $selected = (($post['template'] ?? 'standard') === $value) ? ' selected' : '';
            $label = $template['label'] ?? $value;
            $template_select .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        if ($template_select === '') {
            $template_select = '<option value="standard"' . (($post['template'] ?? 'standard') === 'standard' ? ' selected' : '') . '>Standard</option>';
        }

        $editor = fg_load_editor_options();
        $notification_templates = $editor['variables']['notification_templates'] ?? ['post_update'];
        $notification_options = '';
        foreach ($notification_templates as $template) {
            $selected = (($post['notification_template'] ?? 'post_update') === $template) ? ' selected' : '';
            $notification_options .= '<option value="' . htmlspecialchars($template) . '"' . $selected . '>' . htmlspecialchars(ucwords(str_replace('_', ' ', $template))) . '</option>';
        }

        $channels = fg_load_notification_channels();
        $channel_fields = '';
        foreach ($channels as $key => $channel) {
            $checked = in_array($key, $post['notification_channels'] ?? [], true) ? ' checked' : '';
            $channel_fields .= '<label><input type="checkbox" name="notification_channels[]" value="' . htmlspecialchars($key) . '"' . $checked . '> ' . htmlspecialchars($channel['label'] ?? $key) . '</label>';
        }

        $body = '<section class="panel"><h1>Edit post</h1>';
        $body .= '<form method="post" action="/post.php" enctype="multipart/form-data" class="post-composer" data-preview-target="#composer-preview" data-dataset-target="#composer-elements">';
        $body .= '<input type="hidden" name="post_id" value="' . (int) $post['id'] . '">';
        $body .= '<label>Content<textarea name="content" required data-preview-source>' . htmlspecialchars($post['content'] ?? '') . '</textarea></label>';
        $body .= '<label>Summary<textarea name="summary">' . htmlspecialchars($post['summary'] ?? '') . '</textarea></label>';
        $body .= '<label>Custom type<input type="text" name="custom_type" value="' . htmlspecialchars($post['custom_type'] ?? '') . '"></label>';
        $body .= '<label>Template<select name="template">' . $template_select . '</select></label>';
        $current_format = htmlspecialchars($post['format_options']['content_format'] ?? 'html');
        $body .= '<label>Content format<select name="content_format">';
        foreach (['html' => 'HTML', 'xhtml' => 'XHTML', 'markdown' => 'Markdown (stored as HTML)'] as $value => $label) {
            $selected = ($current_format === $value) ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></label>';
        $body .= '<label>Tags<input type="text" name="tags" value="' . htmlspecialchars(implode(', ', $post['tags'] ?? [])) . '"></label>';
        $body .= '<fieldset class="inline-fieldset"><legend>Display options</legend>';
        $body .= '<label><input type="checkbox" name="display_statistics" value="1"' . (!empty($post['display_options']['show_statistics']) ? ' checked' : '') . '> Show statistics</label>';
        $body .= '<label><input type="checkbox" name="display_embeds" value="1"' . (!empty($post['display_options']['show_embeds']) ? ' checked' : '') . '> Show embeds</label>';
        $body .= '</fieldset>';
        $body .= '<fieldset class="inline-fieldset"><legend>Notification channels</legend>' . $channel_fields . '</fieldset>';
        $body .= '<label>Notification template<select name="notification_template">' . $notification_options . '</select></label>';
        $body .= '<label>Collaborators<input type="text" name="collaborators" value="' . htmlspecialchars(implode(', ', $post['collaborators'] ?? [])) . '"></label>';
        $body .= '<label>Template variables<textarea name="variables" placeholder="{&quot;{key}&quot;:&quot;value&quot;}">' . htmlspecialchars(json_encode($post['variables'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</textarea></label>';
        $module_assignment = null;
        if (!empty($post['content_module']) && is_array($post['content_module'])) {
            $module_assignment = fg_normalize_content_module_definition($post['content_module']);
        }
        if ($module_assignment !== null) {
            $module_snapshot = $module_assignment;
            foreach ($module_snapshot['fields'] as &$snapshot_field) {
                if (is_array($snapshot_field)) {
                    $snapshot_field['value'] = '';
                }
            }
            unset($snapshot_field);
            $module_snapshot['stage'] = '';
            $module_snapshot_json = htmlspecialchars(json_encode($module_snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            $body .= '<input type="hidden" name="content_module_key" value="' . htmlspecialchars($module_assignment['key']) . '">';
            $body .= '<input type="hidden" name="content_module_snapshot" value="' . $module_snapshot_json . '">';
            $body .= '<section class="module-assignment">';
            $body .= '<h2>Guided module</h2>';
            $body .= '<p class="module-intro">' . htmlspecialchars($module_assignment['label']);
            if (!empty($module_assignment['description'])) {
                $body .= ' — ' . htmlspecialchars($module_assignment['description']);
            }
            $body .= '</p>';
            if (!empty($module_assignment['wizard_steps'])) {
                $body .= '<label>Wizard stage<select name="content_module_stage">';
                $current_stage = $module_assignment['stage'] ?? '';
                $first_stage = $module_assignment['wizard_steps'][0] ?? '';
                foreach ($module_assignment['wizard_steps'] as $stage) {
                    $selected = ($current_stage !== '' && strcasecmp($current_stage, $stage) === 0) ? ' selected' : '';
                    if ($current_stage === '' && $first_stage !== '' && strcasecmp($first_stage, $stage) === 0) {
                        $selected = ' selected';
                    }
                    $body .= '<option value="' . htmlspecialchars($stage) . '"' . $selected . '>' . htmlspecialchars($stage) . '</option>';
                }
                $body .= '</select></label>';
            } elseif (!empty($module_assignment['stage'])) {
                $body .= '<input type="hidden" name="content_module_stage" value="' . htmlspecialchars($module_assignment['stage']) . '">';
            }
            if (!empty($module_assignment['fields'])) {
                $body .= '<fieldset class="module-fields"><legend>Module fields</legend>';
                foreach ($module_assignment['fields'] as $field) {
                    $value = $field['value'] ?? '';
                    $body .= '<label>' . htmlspecialchars($field['label']);
                    if (!empty($field['prompt'])) {
                        $body .= '<small>' . htmlspecialchars($field['prompt']) . '</small>';
                    }
                    $body .= '<textarea name="content_module_fields[' . htmlspecialchars($field['key']) . ']" placeholder="' . htmlspecialchars($field['prompt']) . '">' . htmlspecialchars($value) . '</textarea>';
                    $body .= '</label>';
                }
                $body .= '</fieldset>';
            }
            if (!empty($module_assignment['profile_prompts'])) {
                $body .= '<details class="module-prompts"><summary>Profile prompts</summary><ul>';
                foreach ($module_assignment['profile_prompts'] as $prompt) {
                    $body .= '<li>' . htmlspecialchars($prompt) . '</li>';
                }
                $body .= '</ul></details>';
            }
            if (!empty($module_assignment['css_tokens'])) {
                $body .= '<details class="module-css"><summary>CSS tokens</summary><p>';
                foreach ($module_assignment['css_tokens'] as $token) {
                    $body .= '<code>' . htmlspecialchars($token) . '</code> ';
                }
                $body .= '</p></details>';
            }
            $body .= '</section>';
        }
        if (!empty($post['attachments'])) {
            $body .= '<fieldset class="inline-fieldset"><legend>Existing attachments</legend>';
            foreach ($post['attachments'] as $attachment) {
                $id = (int) ($attachment['id'] ?? 0);
                $label = $attachment['original_name'] ?? ('File #' . $id);
                $body .= '<label><input type="checkbox" name="remove_attachments[]" value="' . $id . '"> Remove ' . htmlspecialchars($label) . '</label>';
            }
            $body .= '</fieldset>';
        }
        $body .= '<label>Attachments<input type="file" name="attachments[]" multiple data-upload-input data-max="' . $max_uploads . '"></label>';
        $body .= '<details class="composer-help" data-dataset-name="html5_elements"><summary>HTML5 element support</summary><div class="composer-elements" id="composer-elements" data-dataset-output hidden></div><button type="button" class="dataset-viewer" data-dataset="html5_elements" data-expose="true" data-output="#composer-elements">Load supported elements</button></details>';
        $body .= '<button type="submit">Save changes</button>';
        $body .= '</form></section>';
        $body .= '<section class="panel preview-panel" id="composer-preview" data-preview-output hidden><h2>Live preview</h2><div class="preview-body" data-preview-body><div class="preview-placeholder">Updates appear here with embeds and statistics as you edit.</div></div><div class="preview-embeds" data-preview-embeds hidden></div><dl class="preview-statistics" data-preview-stats hidden></dl><ul class="preview-attachments" data-upload-list hidden></ul></section>';
        fg_render_layout('Edit post', $body);
        return;
    }

    if ($module !== null) {
        $normalized_module = fg_normalize_content_module_definition($module);
        $templates = fg_load_template_options();
        $template_select = '';
        $default_template = 'standard';
        foreach ($templates as $template) {
            $value = $template['name'] ?? '';
            if ($value === '') {
                continue;
            }
            $selected = ($value === $default_template) ? ' selected' : '';
            $label = $template['label'] ?? $value;
            $template_select .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        if ($template_select === '') {
            $template_select = '<option value="standard" selected>Standard</option>';
        }

        $editor = fg_load_editor_options();
        $notification_templates = $editor['variables']['notification_templates'] ?? ['post_update'];
        $notification_options = '';
        foreach ($notification_templates as $template) {
            $notification_options .= '<option value="' . htmlspecialchars($template) . '">' . htmlspecialchars(ucwords(str_replace('_', ' ', $template))) . '</option>';
        }

        $channels = fg_load_notification_channels();
        $channel_fields = '';
        foreach ($channels as $key => $channel) {
            $channel_fields .= '<label><input type="checkbox" name="notification_channels[]" value="' . htmlspecialchars($key) . '" checked> ' . htmlspecialchars($channel['label'] ?? $key) . '</label>';
        }

        $module_snapshot = $normalized_module;
        foreach ($module_snapshot['fields'] as &$snapshot_field) {
            if (is_array($snapshot_field)) {
                $snapshot_field['value'] = '';
            }
        }
        unset($snapshot_field);
        $module_snapshot['stage'] = '';
        $module_snapshot_json = htmlspecialchars(json_encode($module_snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

        $default_summary = $normalized_module['description'] ?? '';
        $default_custom_type = $normalized_module['label'] ?? '';
        $default_tags = implode(', ', $normalized_module['categories'] ?? []);

        $body = '<section class="panel module-composer">';
        $body .= '<h1>' . htmlspecialchars($normalized_module['label']) . '</h1>';
        if ($normalized_module['description'] !== '') {
            $body .= '<p class="module-intro">' . htmlspecialchars($normalized_module['description']) . '</p>';
        }
        if (!empty($normalized_module['wizard_steps'])) {
            $body .= '<ol class="module-wizard">';
            foreach ($normalized_module['wizard_steps'] as $step) {
                $body .= '<li>' . htmlspecialchars($step) . '</li>';
            }
            $body .= '</ol>';
        }
        $body .= '<form method="post" action="/post.php" enctype="multipart/form-data" class="post-composer" data-preview-target="#composer-preview" data-dataset-target="#composer-elements">';
        $body .= '<input type="hidden" name="content_module_key" value="' . htmlspecialchars($normalized_module['key']) . '">';
        $body .= '<input type="hidden" name="content_module_snapshot" value="' . $module_snapshot_json . '">';
        $body .= '<label>Content<textarea name="content" required data-preview-source placeholder="Share the full body for this module"></textarea></label>';
        $body .= '<label>Summary<textarea name="summary" placeholder="Short overview for notifications and cards">' . htmlspecialchars($default_summary) . '</textarea></label>';
        $body .= '<label>Custom type<input type="text" name="custom_type" value="' . htmlspecialchars($default_custom_type) . '" placeholder="article, gallery, event…"></label>';
        $body .= '<label>Template<select name="template">' . $template_select . '</select></label>';
        $body .= '<label>Content format<select name="content_format"><option value="html" selected>HTML</option><option value="xhtml">XHTML</option><option value="markdown">Markdown (stored as HTML)</option></select></label>';
        $body .= '<label>Tags<input type="text" name="tags" value="' . htmlspecialchars($default_tags) . '" placeholder="design, release, changelog"></label>';
        $body .= '<fieldset class="inline-fieldset"><legend>Display options</legend>';
        $body .= '<label><input type="checkbox" name="display_statistics" value="1" checked> Show statistics</label>';
        $body .= '<label><input type="checkbox" name="display_embeds" value="1" checked> Show embeds</label>';
        $body .= '</fieldset>';
        if (!empty($normalized_module['wizard_steps'])) {
            $body .= '<label>Wizard stage<select name="content_module_stage">';
            foreach ($normalized_module['wizard_steps'] as $index => $stage) {
                $selected = $index === 0 ? ' selected' : '';
                $body .= '<option value="' . htmlspecialchars($stage) . '"' . $selected . '>' . htmlspecialchars($stage) . '</option>';
            }
            $body .= '</select></label>';
        }
        if (!empty($normalized_module['fields'])) {
            $body .= '<fieldset class="module-fields"><legend>Module fields</legend>';
            foreach ($normalized_module['fields'] as $field) {
                $body .= '<label>' . htmlspecialchars($field['label']);
                if (!empty($field['prompt'])) {
                    $body .= '<small>' . htmlspecialchars($field['prompt']) . '</small>';
                }
                $body .= '<textarea name="content_module_fields[' . htmlspecialchars($field['key']) . ']" placeholder="' . htmlspecialchars($field['prompt']) . '"></textarea>';
                $body .= '</label>';
            }
            $body .= '</fieldset>';
        }
        if (!empty($normalized_module['profile_prompts'])) {
            $body .= '<details class="module-prompts"><summary>Profile prompts</summary><ul>';
            foreach ($normalized_module['profile_prompts'] as $prompt) {
                $body .= '<li>' . htmlspecialchars($prompt) . '</li>';
            }
            $body .= '</ul></details>';
        }
        if (!empty($normalized_module['css_tokens'])) {
            $body .= '<details class="module-css"><summary>CSS tokens</summary><p>';
            foreach ($normalized_module['css_tokens'] as $token) {
                $body .= '<code>' . htmlspecialchars($token) . '</code> ';
            }
            $body .= '</p></details>';
        }
        $body .= '<fieldset class="inline-fieldset"><legend>Notification channels</legend>' . $channel_fields . '</fieldset>';
        $body .= '<label>Notification template<select name="notification_template">' . $notification_options . '</select></label>';
        $body .= '<label>Collaborators (usernames, comma separated)<input type="text" name="collaborators" placeholder="alex, taylor"></label>';
        $body .= '<label>Attachments<input type="file" name="attachments[]" multiple data-upload-input data-max="' . $max_uploads . '"></label>';
        $body .= '<details class="composer-help" data-dataset-name="editor_options" id="composer-editor-options"><summary>Editor controls</summary><div class="composer-elements" data-dataset-output hidden></div><button type="button" class="dataset-viewer" data-dataset="editor_options" data-expose="true" data-output="#composer-editor-options [data-dataset-output]">Load editor reference</button></details>';
        $body .= '<details class="composer-help" data-dataset-name="html5_elements"><summary>HTML5 element support</summary><div class="composer-elements" id="composer-elements" data-dataset-output hidden></div><button type="button" class="dataset-viewer" data-dataset="html5_elements" data-expose="true" data-output="#composer-elements">Load supported elements</button></details>';
        $body .= '<fieldset class="inline-fieldset"><legend>Conversation & privacy</legend>';
        $body .= '<label>Conversation style<select name="conversation_style">';
        foreach (['standard' => 'Standard', 'threaded' => 'Threaded', 'broadcast' => 'Broadcast'] as $value => $label) {
            $body .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></label>';
        $body .= '<label>Privacy<select name="privacy">';
        foreach (['public' => 'Public', 'connections' => 'Connections', 'private' => 'Private'] as $value => $label) {
            $body .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
        }
        $body .= '</select></label>';
        $body .= '</fieldset>';
        $body .= '<button type="submit">Publish module entry</button>';
        $body .= '</form>';
        $body .= '</section>';
        $body .= '<section class="panel preview-panel" id="composer-preview" data-preview-output hidden><h2>Live preview</h2><div class="preview-body" data-preview-body><div class="preview-placeholder">Start writing to see your live preview, embeds, and statistics.</div></div><div class="preview-embeds" data-preview-embeds hidden></div><dl class="preview-statistics" data-preview-stats hidden></dl><ul class="preview-attachments" data-upload-list hidden></ul></section>';

        fg_render_layout('Module composer', $body);
        return;
    }

    header('Location: /index.php');
    exit;
}
