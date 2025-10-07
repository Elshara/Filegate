<?php

require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/current_user.php';

function fg_render_layout(string $title, string $body, array $options = []): void
{
    $app_name = fg_get_setting('app_name', 'Filegate');
    $current = fg_current_user();
    $nav = $options['nav'] ?? true;
    $extra_head = $options['head'] ?? '';

    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . htmlspecialchars($title . ' · ' . $app_name) . '</title>';
    echo '<link rel="stylesheet" href="/assets/css/global/style.css">';
    echo $extra_head;
    echo '</head>';
    echo '<body>';
    echo '<header class="app-header">';
    echo '<div class="app-title">' . htmlspecialchars($app_name) . '</div>';
    if ($nav) {
        echo '<nav class="app-nav">';
        if ($current) {
            echo '<a href="/index.php">Feed</a>';
            echo '<a href="/profile.php?user=' . urlencode($current['username']) . '">My Profile</a>';
            echo '<a href="/settings.php">Settings</a>';
            echo '<form method="post" action="/logout.php" class="logout-form"><button type="submit">Sign out</button></form>';
        } else {
            echo '<a href="/login.php">Sign in</a>';
            echo '<a href="/register.php">Create account</a>';
        }
        echo '</nav>';
    }
    echo '</header>';
    echo '<main class="app-main">' . $body . '</main>';
    echo '</body>';
    echo '</html>';
}

