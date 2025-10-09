<?php

require_once __DIR__ . '/load_posts.php';
require_once __DIR__ . '/save_posts.php';

function fg_toggle_like(int $post_id, int $user_id): ?array
{
    $posts = fg_load_posts();

    foreach ($posts['records'] as $index => $post) {
        if ((int) $post['id'] === $post_id) {
            $likes = $post['likes'] ?? [];
            $key = array_search($user_id, $likes, true);
            if ($key !== false) {
                unset($likes[$key]);
            } else {
                $likes[] = $user_id;
            }
            $post['likes'] = array_values($likes);
            $posts['records'][$index] = $post;
            fg_save_posts($posts);
            return $post;
        }
    }

    return null;
}

