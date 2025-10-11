<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/load_uploads.php';
require_once __DIR__ . '/find_post_by_id.php';
require_once __DIR__ . '/guard_asset.php';

function fg_public_media_controller(): void
{
    fg_bootstrap();
    $user = fg_require_login();
    fg_guard_asset('assets/php/media_controller.php', [
        'role' => $user['role'] ?? null,
        'user_id' => $user['id'] ?? null,
    ]);
    $uploads = fg_load_uploads();
    $records = $uploads['records'] ?? [];
    $upload_id = isset($_GET['upload']) ? (int) $_GET['upload'] : 0;
    $match = null;

    foreach ($records as $record) {
        if ((int) ($record['id'] ?? 0) === $upload_id) {
            $match = $record;
            break;
        }
    }

    if (!$match || !isset($match['path']) || !file_exists($match['path'])) {
        http_response_code(404);
        echo 'File not found.';
        return;
    }

    $post_id = $match['meta']['post_id'] ?? null;
    if ($post_id) {
        $post = fg_find_post_by_id((int) $post_id);
        if ($post) {
            $author_id = (int) ($post['author_id'] ?? 0);
            $is_collaborator = in_array($user['username'], $post['collaborators'] ?? [], true);
            $is_owner = (int) $user['id'] === $author_id;
            $is_private = ($post['privacy'] ?? 'public') === 'private';
            if ($is_private && !$is_owner && !$is_collaborator) {
                http_response_code(403);
                echo 'Access denied.';
                return;
            }
        }
    }

    $mime = $match['mime_type'] ?? 'application/octet-stream';
    $size = isset($match['size']) ? (int) $match['size'] : filesize($match['path']);
    $filename = $match['original_name'] ?? basename($match['path']);

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $size);
    header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
    readfile($match['path']);
}
