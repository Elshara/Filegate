<?php

require_once __DIR__ . '/../global/bootstrap.php';
require_once __DIR__ . '/../global/require_login.php';
require_once __DIR__ . '/../global/add_post.php';
require_once __DIR__ . '/../global/update_post.php';
require_once __DIR__ . '/../global/find_post_by_id.php';
require_once __DIR__ . '/../global/parse_collaborators.php';
require_once __DIR__ . '/../global/render_layout.php';
require_once __DIR__ . '/../global/store_upload.php';
require_once __DIR__ . '/../global/get_setting.php';
require_once __DIR__ . '/../global/load_template_options.php';
require_once __DIR__ . '/../global/load_editor_options.php';
require_once __DIR__ . '/../global/load_notification_channels.php';
require_once __DIR__ . '/../global/update_upload_meta.php';
require_once __DIR__ . '/../global/guard_asset.php';

function fg_public_post_controller(): void
{
    fg_bootstrap();
    $user = fg_require_login();
    fg_guard_asset('assets/php/public/post_controller.php', [
        'role' => $user['role'] ?? null,
        'user_id' => $user['id'] ?? null,
    ]);
    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
    $post = $post_id ? fg_find_post_by_id($post_id) : null;
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

    header('Location: /index.php');
    exit;
}
