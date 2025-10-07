<?php

require_once __DIR__ . '/../global/bootstrap.php';
require_once __DIR__ . '/../global/find_user_by_username.php';
require_once __DIR__ . '/../global/hash_password.php';
require_once __DIR__ . '/../global/upsert_user.php';
require_once __DIR__ . '/../global/log_in_user.php';
require_once __DIR__ . '/../global/current_user.php';
require_once __DIR__ . '/../global/load_users.php';
require_once __DIR__ . '/../pages/render_register.php';
require_once __DIR__ . '/../global/guard_asset.php';

function fg_public_register_controller(): void
{
    fg_bootstrap();
    $current = fg_current_user();
    fg_guard_asset('assets/php/public/register_controller.php', [
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
        $display_name = trim($_POST['display_name'] ?? '');
        $bio = $_POST['bio'] ?? '';

        if ($username === '' || $password === '' || $display_name === '') {
            fg_render_register_page(['error' => 'All fields are required.']);
            return;
        }

        if (fg_find_user_by_username($username)) {
            fg_render_register_page(['error' => 'Username already exists.']);
            return;
        }

        $users = fg_load_users();
        $role = empty($users['records']) ? 'admin' : 'member';

        $user = fg_upsert_user([
            'username' => $username,
            'password' => fg_hash_password($password),
            'display_name' => $display_name,
            'bio' => $bio,
            'privacy' => 'public',
            'role' => $role,
        ]);

        fg_log_in_user((int) $user['id']);
        header('Location: /index.php');
        exit;
    }

    fg_render_register_page();
}
