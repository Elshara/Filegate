<?php

require_once __DIR__ . '/load_posts.php';
require_once __DIR__ . '/save_posts.php';
require_once __DIR__ . '/sanitize_html.php';

function fg_add_post(array $post): array
{
    $posts = fg_load_posts();
    $post['id'] = $posts['next_id'] ?? 1;
    $posts['next_id'] = ($posts['next_id'] ?? 1) + 1;
    $post['content'] = fg_sanitize_html($post['content'] ?? '');
    $post['created_at'] = $post['created_at'] ?? date(DATE_ATOM);
    $post['updated_at'] = $post['updated_at'] ?? $post['created_at'];
    $post['likes'] = $post['likes'] ?? [];
    $post['collaborators'] = $post['collaborators'] ?? [];
    $post['custom_type'] = trim($post['custom_type'] ?? '');
    $post['privacy'] = $post['privacy'] ?? 'public';
    $post['conversation_style'] = $post['conversation_style'] ?? 'standard';
    $posts['records'][] = $post;
    fg_save_posts($posts);

    return $post;
}

