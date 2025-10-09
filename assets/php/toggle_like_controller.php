<?php

require_once __DIR__ . '/../global/bootstrap.php';
require_once __DIR__ . '/../global/require_login.php';
require_once __DIR__ . '/../global/toggle_like.php';
require_once __DIR__ . '/../global/is_ajax_request.php';
require_once __DIR__ . '/../global/render_json_response.php';
require_once __DIR__ . '/../global/find_post_by_id.php';
require_once __DIR__ . '/../global/guard_asset.php';

function fg_public_toggle_like_controller(): void
{
    fg_bootstrap();
    $user = fg_require_login();
    fg_guard_asset('assets/php/public/toggle_like_controller.php', [
        'role' => $user['role'] ?? null,
        'user_id' => $user['id'] ?? null,
    ]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $post_id = (int) ($_POST['post_id'] ?? 0);
        fg_toggle_like($post_id, (int) $user['id']);
        if (fg_is_ajax_request()) {
            $post = fg_find_post_by_id($post_id);
            if ($post === null) {
                fg_render_json_response(['status' => 'error', 'message' => 'Post not found.'], 404);
                return;
            }
            $likes = $post['likes'] ?? [];
            $liked = in_array((int) $user['id'], array_map('intval', $likes), true);
            fg_render_json_response([
                'status' => 'ok',
                'post_id' => $post_id,
                'likes' => count($likes),
                'liked' => $liked,
            ]);
            return;
        }
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php'));
    exit;
}
