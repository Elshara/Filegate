<?php

require_once __DIR__ . '/load_posts.php';

function fg_find_post_by_id(int $post_id): ?array
{
    $posts = fg_load_posts();
    foreach ($posts['records'] as $post) {
        if ((int) $post['id'] === $post_id) {
            return $post;
        }
    }

    return null;
}

