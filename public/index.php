<?php

require_once __DIR__ . '/../assets/php/global/bootstrap.php';
require_once __DIR__ . '/../assets/php/global/require_login.php';
require_once __DIR__ . '/../assets/php/pages/render_feed.php';
require_once __DIR__ . '/../assets/php/global/render_layout.php';
require_once __DIR__ . '/../assets/php/global/guard_asset.php';

fg_bootstrap();
$user = fg_require_login();
fg_guard_asset('public/index.php', [
    'role' => $user['role'] ?? null,
    'user_id' => $user['id'] ?? null,
]);
$body = fg_render_feed($user);
fg_render_layout('Home', $body);

