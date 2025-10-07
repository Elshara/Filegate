<?php

require_once __DIR__ . '/load_posts.php';
require_once __DIR__ . '/save_posts.php';
require_once __DIR__ . '/sanitize_html.php';

function fg_update_post(array $post): ?array
{
    $posts = fg_load_posts();

    foreach ($posts['records'] as $index => $existing) {
        if ((int) $existing['id'] === (int) ($post['id'] ?? 0)) {
            $post['content'] = fg_sanitize_html($post['content'] ?? $existing['content']);
            $post['updated_at'] = date(DATE_ATOM);
            $posts['records'][$index] = array_merge($existing, $post);
            fg_save_posts($posts);
            return $posts['records'][$index];
        }
    }

    return null;
}

