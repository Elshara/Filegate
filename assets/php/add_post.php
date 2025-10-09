<?php

require_once __DIR__ . '/load_posts.php';
require_once __DIR__ . '/save_posts.php';
require_once __DIR__ . '/sanitize_html.php';
require_once __DIR__ . '/collect_embeds.php';
require_once __DIR__ . '/calculate_post_statistics.php';
require_once __DIR__ . '/queue_notification.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/find_user_by_id.php';

function fg_add_post(array $post): array
{
    $posts = fg_load_posts();
    $post['id'] = $posts['next_id'] ?? 1;
    $posts['next_id'] = ($posts['next_id'] ?? 1) + 1;
    $post['content'] = fg_sanitize_html($post['content'] ?? '');
    $post['summary'] = trim((string) ($post['summary'] ?? ''));
    $post['tags'] = array_values(array_filter(array_map('trim', $post['tags'] ?? [])));
    $post['notification_template'] = $post['notification_template'] ?? 'post_update';
    $post['notification_channels'] = $post['notification_channels'] ?? [];
    $post['template'] = $post['template'] ?? 'standard';
    $post['format_options'] = $post['format_options'] ?? ['content_format' => 'html'];
    $post['display_options'] = $post['display_options'] ?? ['show_statistics' => true, 'show_embeds' => true];
    $post['variables'] = $post['variables'] ?? [];
    $post['attachments'] = array_values($post['attachments'] ?? []);
    $post['embeds'] = fg_collect_embeds($post['content']);
    $post['statistics'] = fg_calculate_post_statistics($post['content'], $post['embeds']);
    $post['created_at'] = $post['created_at'] ?? date(DATE_ATOM);
    $post['updated_at'] = $post['updated_at'] ?? $post['created_at'];
    $post['likes'] = $post['likes'] ?? [];
    $post['collaborators'] = $post['collaborators'] ?? [];
    $post['custom_type'] = trim((string) ($post['custom_type'] ?? ''));
    $post['privacy'] = $post['privacy'] ?? 'public';
    $post['conversation_style'] = $post['conversation_style'] ?? 'standard';
    $posts['records'][] = $post;
    fg_save_posts($posts);

    $app_name = fg_get_setting('app_name', 'Filegate');
    $author = fg_find_user_by_id((int) ($post['author_id'] ?? 0));
    $variables = array_merge([
        '{app_name}' => $app_name,
        '{author}' => $author['display_name'] ?? $author['username'] ?? 'Member',
        '{post_title}' => $post['custom_type'] !== '' ? $post['custom_type'] : 'New post',
        '{post_url}' => '/index.php#post-' . $post['id'],
        '{summary}' => $post['summary'] ?? '',
        '{username}' => $author['username'] ?? '',
    ], $post['variables']);

    fg_queue_notification([
        'type' => 'post.created',
        'post_id' => $post['id'],
        'author_id' => $post['author_id'] ?? null,
        'template' => $post['notification_template'],
        'variables' => $variables,
        'subject' => $variables['{app_name}'] . ': ' . ($post['custom_type'] !== '' ? $post['custom_type'] : 'New update'),
        'body' => $post['summary'] !== '' ? $post['summary'] : strip_tags($post['content']),
    ], $post['notification_channels']);

    return $post;
}

