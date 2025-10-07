<?php

require_once __DIR__ . '/../assets/php/global/bootstrap.php';
require_once __DIR__ . '/../assets/php/global/log_out_user.php';

fg_bootstrap();
fg_log_out_user();
header('Location: /login.php');
exit;

