<?php

require_once __DIR__ . '/../global/render_layout.php';

function fg_render_register_page(array $context = []): void
{
    $error = $context['error'] ?? '';
    $body = '<section class="panel">';
    $body .= '<h1>Create your profile</h1>';
    if ($error !== '') {
        $body .= '<p class="error">' . htmlspecialchars($error) . '</p>';
    }
    $body .= '<form method="post" action="/register.php">';
    $body .= '<label>Username<input type="text" name="username" required></label>';
    $body .= '<label>Password<input type="password" name="password" required></label>';
    $body .= '<label>Display name<input type="text" name="display_name" required></label>';
    $body .= '<label>Profile summary<textarea name="bio" placeholder="Share a bit about yourself"></textarea></label>';
    $body .= '<button type="submit">Create profile</button>';
    $body .= '</form>';
    $body .= '<p>Already have an account? <a href="/login.php">Sign in</a>.</p>';
    $body .= '</section>';

    fg_render_layout('Register', $body, ['nav' => false]);
}

