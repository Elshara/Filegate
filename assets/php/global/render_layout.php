<?php

require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/current_user.php';

function fg_render_layout(string $title, string $body, array $options = []): void
{
    $app_name = fg_get_setting('app_name', 'Filegate');
    $embed_policy = fg_get_setting('rich_embed_policy', 'enabled');
    $statistics_policy = fg_get_setting('statistics_visibility', 'public');
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
    echo '<script src="/assets/js/global/detect_embed.js" defer></script>';
    echo '<script src="/assets/js/global/render_embed_fragment.js" defer></script>';
    echo '<script src="/assets/js/global/extract_urls.js" defer></script>';
    echo '<script src="/assets/js/global/sanitize_preview_html.js" defer></script>';
    echo '<script src="/assets/js/global/calculate_preview_statistics.js" defer></script>';
    echo '<script src="/assets/js/global/register_ajax_forms.js" defer></script>';
    echo '<script src="/assets/js/global/register_post_preview.js" defer></script>';
    echo '<script src="/assets/js/global/register_embed_observer.js" defer></script>';
    echo '<script src="/assets/js/global/register_dataset_viewer.js" defer></script>';
    echo '<script src="/assets/js/global/init_client.js" defer></script>';
    echo $extra_head;
    echo '</head>';
    echo '<body data-embed-policy="' . htmlspecialchars($embed_policy) . '" data-statistics-visibility="' . htmlspecialchars($statistics_policy) . '">';
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
    echo '<script>document.addEventListener("DOMContentLoaded",function(){if(typeof window.fg_initClient==="function"){window.fg_initClient();}});</script>';
    echo '</body>';
    echo '</html>';
}

