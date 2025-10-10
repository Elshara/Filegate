<?php

require_once __DIR__ . '/load_posts.php';
require_once __DIR__ . '/save_posts.php';
require_once __DIR__ . '/sanitize_html.php';
require_once __DIR__ . '/collect_embeds.php';
require_once __DIR__ . '/calculate_post_statistics.php';
require_once __DIR__ . '/queue_notification.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/find_user_by_id.php';
require_once __DIR__ . '/normalize_content_module.php';

function fg_update_post(array $post): ?array
{
    $posts = fg_load_posts();

    foreach ($posts['records'] as $index => $existing) {
        if ((int) $existing['id'] === (int) ($post['id'] ?? 0)) {
            $post['content'] = fg_sanitize_html($post['content'] ?? $existing['content']);
            $post['summary'] = trim((string) ($post['summary'] ?? ($existing['summary'] ?? '')));
            $tags = $post['tags'] ?? ($existing['tags'] ?? []);
            $post['tags'] = array_values(array_filter(array_map('trim', $tags)));
            $post['notification_template'] = $post['notification_template'] ?? ($existing['notification_template'] ?? 'post_update');
            $post['notification_channels'] = $post['notification_channels'] ?? ($existing['notification_channels'] ?? []);
            $post['template'] = $post['template'] ?? ($existing['template'] ?? 'standard');
            $post['format_options'] = $post['format_options'] ?? ($existing['format_options'] ?? ['content_format' => 'html']);
            $post['display_options'] = $post['display_options'] ?? ($existing['display_options'] ?? ['show_statistics' => true, 'show_embeds' => true]);
            $post['variables'] = $post['variables'] ?? ($existing['variables'] ?? []);
            $post['attachments'] = array_values($post['attachments'] ?? ($existing['attachments'] ?? []));
            if (!empty($post['content_module']) && is_array($post['content_module'])) {
                $module_assignment = fg_normalize_content_module_definition($post['content_module']);
                $normalized_fields = [];
                foreach ($module_assignment['fields'] as $field) {
                    if (!is_array($field) || empty($field['key'])) {
                        continue;
                    }
                    $field['value'] = is_string($field['value'] ?? '') ? trim((string) $field['value']) : '';
                    $normalized_fields[] = $field;
                }
                $module_assignment['fields'] = $normalized_fields;
                $module_assignment['stage'] = trim((string) ($module_assignment['stage'] ?? ''));
                $post['content_module'] = $module_assignment;
            } elseif (!empty($existing['content_module']) && is_array($existing['content_module'])) {
                $post['content_module'] = fg_normalize_content_module_definition($existing['content_module']);
            }
            $post['embeds'] = fg_collect_embeds($post['content']);
            $post['statistics'] = fg_calculate_post_statistics($post['content'], $post['embeds']);
            $post['updated_at'] = date(DATE_ATOM);
            $posts['records'][$index] = array_merge($existing, $post);
            fg_save_posts($posts);

            $updated = $posts['records'][$index];
            $app_name = fg_get_setting('app_name', 'Filegate');
            $author = fg_find_user_by_id((int) ($updated['author_id'] ?? 0));
            $variables = array_merge([
                '{app_name}' => $app_name,
                '{author}' => $author['display_name'] ?? $author['username'] ?? 'Member',
                '{post_title}' => $updated['custom_type'] !== '' ? $updated['custom_type'] : 'Updated post',
                '{post_url}' => '/index.php#post-' . $updated['id'],
                '{summary}' => $updated['summary'] ?? '',
                '{username}' => $author['username'] ?? '',
            ], $updated['variables'] ?? []);

            fg_queue_notification([
                'type' => 'post.updated',
                'post_id' => $updated['id'],
                'author_id' => $updated['author_id'] ?? null,
                'template' => $updated['notification_template'] ?? 'post_update',
                'variables' => $variables,
                'subject' => $variables['{app_name}'] . ': ' . ($updated['custom_type'] !== '' ? $updated['custom_type'] : 'Post updated'),
                'body' => $updated['summary'] !== '' ? $updated['summary'] : strip_tags($updated['content']),
            ], $updated['notification_channels'] ?? []);

            return $updated;
        }
    }

    return null;
}

