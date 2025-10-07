<?php

require_once __DIR__ . '/../assets/php/global/bootstrap.php';
require_once __DIR__ . '/../assets/php/global/log_out_user.php';
require_once __DIR__ . '/../assets/php/global/current_user.php';
require_once __DIR__ . '/../assets/php/global/guard_asset.php';

fg_bootstrap();
$current = fg_current_user();
fg_guard_asset('public/logout.php', [
    'role' => $current['role'] ?? null,
    'user_id' => $current['id'] ?? null,
]);
fg_log_out_user();
header('Location: /login.php');
exit;

