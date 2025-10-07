<?php

require_once __DIR__ . '/../assets/php/global/bootstrap.php';
require_once __DIR__ . '/../assets/php/global/require_login.php';
require_once __DIR__ . '/../assets/php/global/toggle_like.php';

fg_bootstrap();
$user = fg_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = (int) ($_POST['post_id'] ?? 0);
    fg_toggle_like($post_id, (int) $user['id']);
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php'));
exit;

