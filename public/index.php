<?php

require_once __DIR__ . '/../assets/php/global/bootstrap.php';
require_once __DIR__ . '/../assets/php/global/require_login.php';
require_once __DIR__ . '/../assets/php/pages/render_feed.php';
require_once __DIR__ . '/../assets/php/global/render_layout.php';

fg_bootstrap();
$user = fg_require_login();
$body = fg_render_feed($user);
fg_render_layout('Home', $body);

