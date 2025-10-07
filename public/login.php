<?php

require_once __DIR__ . '/../assets/php/global/bootstrap.php';
require_once __DIR__ . '/../assets/php/global/find_user_by_username.php';
require_once __DIR__ . '/../assets/php/global/verify_password.php';
require_once __DIR__ . '/../assets/php/global/log_in_user.php';
require_once __DIR__ . '/../assets/php/global/current_user.php';
require_once __DIR__ . '/../assets/php/pages/render_login.php';

fg_bootstrap();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = fg_find_user_by_username($username);

    if (!$user || !fg_verify_password($password, $user['password'] ?? '')) {
        fg_render_login_page(['error' => 'Invalid credentials.']);
        return;
    }

    fg_log_in_user((int) $user['id']);
    header('Location: /index.php');
    exit;
}

if (fg_current_user()) {
    header('Location: /index.php');
    exit;
}

fg_render_login_page();

