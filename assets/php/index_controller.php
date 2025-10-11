<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/render_feed.php';
require_once __DIR__ . '/render_layout.php';
require_once __DIR__ . '/guard_asset.php';

function fg_public_index_controller(): void
{
    fg_bootstrap();
    $user = fg_require_login();
    fg_guard_asset('assets/php/index_controller.php', [
        'role' => $user['role'] ?? null,
        'user_id' => $user['id'] ?? null,
    ]);
    $body = fg_render_feed($user);
    fg_render_layout('Home', $body);
}
