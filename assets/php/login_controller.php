<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/find_user_by_username.php';
require_once __DIR__ . '/verify_password.php';
require_once __DIR__ . '/log_in_user.php';
require_once __DIR__ . '/current_user.php';
require_once __DIR__ . '/render_login.php';
require_once __DIR__ . '/guard_asset.php';

function fg_public_login_controller(): void
{
    fg_bootstrap();
    $current = fg_current_user();
    fg_guard_asset('assets/php/login_controller.php', [
        'role' => $current['role'] ?? null,
        'user_id' => $current['id'] ?? null,
    ]);

    if ($current) {
        header('Location: /index.php');
        exit;
    }

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

    fg_render_login_page();
}
