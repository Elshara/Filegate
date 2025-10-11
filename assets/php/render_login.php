<?php

require_once __DIR__ . '/render_layout.php';

function fg_render_login_page(array $context = []): void
{
    $error = $context['error'] ?? '';
    $body = '<section class="panel">';
    $body .= '<h1>Sign in</h1>';
    if ($error !== '') {
        $body .= '<p class="error">' . htmlspecialchars($error) . '</p>';
    }
    $body .= '<form method="post" action="/login.php">';
    $body .= '<label>Username<input type="text" name="username" required></label>';
    $body .= '<label>Password<input type="password" name="password" required></label>';
    $body .= '<button type="submit">Sign in</button>';
    $body .= '</form>';
    $body .= '<p>Need an account? <a href="/register.php">Create one</a>.</p>';
    $body .= '</section>';

    fg_render_layout('Sign in', $body, ['nav' => false]);
}

