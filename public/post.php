<?php

require_once __DIR__ . '/../assets/php/global/bootstrap.php';
require_once __DIR__ . '/../assets/php/global/require_login.php';
require_once __DIR__ . '/../assets/php/global/add_post.php';
require_once __DIR__ . '/../assets/php/global/update_post.php';
require_once __DIR__ . '/../assets/php/global/find_post_by_id.php';
require_once __DIR__ . '/../assets/php/global/parse_collaborators.php';
require_once __DIR__ . '/../assets/php/global/render_layout.php';

fg_bootstrap();
$user = fg_require_login();
$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
$post = $post_id ? fg_find_post_by_id($post_id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'author_id' => $user['id'],
        'content' => $_POST['content'] ?? '',
        'custom_type' => trim($_POST['custom_type'] ?? ''),
        'conversation_style' => $_POST['conversation_style'] ?? 'standard',
        'privacy' => $_POST['privacy'] ?? 'public',
        'collaborators' => fg_parse_collaborators($_POST['collaborators'] ?? ''),
    ];

    if (isset($_POST['post_id'])) {
        $payload['id'] = (int) $_POST['post_id'];
        $existing = fg_find_post_by_id($payload['id']);
        if ($existing && ((int) $existing['author_id'] === (int) $user['id'] || in_array($user['username'], $existing['collaborators'] ?? [], true))) {
            fg_update_post(array_merge($existing, $payload));
            header('Location: /index.php#post-' . $payload['id']);
            exit;
        }
    } else {
        $created = fg_add_post($payload);
        header('Location: /index.php#post-' . $created['id']);
        exit;
    }
}

if ($post && ((int) $post['author_id'] === (int) $user['id'] || in_array($user['username'], $post['collaborators'] ?? [], true))) {
    $body = '<section class="panel"><h1>Edit post</h1>';
    $body .= '<form method="post" action="/post.php">';
    $body .= '<input type="hidden" name="post_id" value="' . (int) $post['id'] . '">';
    $body .= '<label>Content<textarea name="content" required>' . htmlspecialchars($post['content'] ?? '') . '</textarea></label>';
    $body .= '<label>Custom type<input type="text" name="custom_type" value="' . htmlspecialchars($post['custom_type'] ?? '') . '"></label>';
    $body .= '<label>Conversation style<select name="conversation_style">';
    foreach (['standard' => 'Standard', 'threaded' => 'Threaded', 'broadcast' => 'Broadcast'] as $value => $label) {
        $selected = (($post['conversation_style'] ?? 'standard') === $value) ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></label>';
    $body .= '<label>Privacy<select name="privacy">';
    foreach (['public' => 'Public', 'connections' => 'Connections', 'private' => 'Private'] as $value => $label) {
        $selected = (($post['privacy'] ?? 'public') === $value) ? ' selected' : '';
        $body .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    $body .= '</select></label>';
    $body .= '<label>Collaborators<input type="text" name="collaborators" value="' . htmlspecialchars(implode(', ', $post['collaborators'] ?? [])) . '"></label>';
    $body .= '<button type="submit">Save changes</button>';
    $body .= '</form></section>';
    fg_render_layout('Edit post', $body);
    return;
}

header('Location: /index.php');
exit;

