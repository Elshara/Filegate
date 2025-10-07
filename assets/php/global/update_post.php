<?php

require_once __DIR__ . '/load_posts.php';
require_once __DIR__ . '/save_posts.php';
require_once __DIR__ . '/sanitize_html.php';
require_once __DIR__ . '/collect_embeds.php';
require_once __DIR__ . '/calculate_post_statistics.php';

function fg_update_post(array $post): ?array
{
    $posts = fg_load_posts();

    foreach ($posts['records'] as $index => $existing) {
        if ((int) $existing['id'] === (int) ($post['id'] ?? 0)) {
            $post['content'] = fg_sanitize_html($post['content'] ?? $existing['content']);
            $post['embeds'] = fg_collect_embeds($post['content']);
            $post['statistics'] = fg_calculate_post_statistics($post['content'], $post['embeds']);
            $post['updated_at'] = date(DATE_ATOM);
            $posts['records'][$index] = array_merge($existing, $post);
            fg_save_posts($posts);
            return $posts['records'][$index];
        }
    }

    return null;
}

