<?php

require_once __DIR__ . '/../global/bootstrap.php';
require_once __DIR__ . '/../global/log_out_user.php';
require_once __DIR__ . '/../global/current_user.php';
require_once __DIR__ . '/../global/guard_asset.php';

function fg_public_logout_controller(): void
{
    fg_bootstrap();
    $current = fg_current_user();
    fg_guard_asset('assets/php/public/logout_controller.php', [
        'role' => $current['role'] ?? null,
        'user_id' => $current['id'] ?? null,
    ]);
    fg_log_out_user();
    header('Location: /login.php');
    exit;
}
