<?php

require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/current_user.php';
require_once __DIR__ . '/get_asset_parameter_value.php';
require_once __DIR__ . '/is_admin.php';

function fg_render_layout(string $title, string $body, array $options = []): void
{
    $app_name = fg_get_setting('app_name', 'Filegate');
    $embed_policy = fg_get_setting('rich_embed_policy', 'enabled');
    $statistics_policy = fg_get_setting('statistics_visibility', 'public');
    $current = fg_current_user();
    $nav = $options['nav'] ?? true;
    $extra_head = $options['head'] ?? '';
    $context = [
        'role' => $current['role'] ?? null,
        'user_id' => $current['id'] ?? null,
    ];
    $layoutEnabled = fg_get_asset_parameter_value('assets/php/global/render_layout.php', 'enabled', $context);
    $layoutMode = fg_get_asset_parameter_value('assets/php/global/render_layout.php', 'mode', $context);
    $layoutVariant = fg_get_asset_parameter_value('assets/php/global/render_layout.php', 'variant', $context);

    $classFragments = ['layout-shell'];
    if (!$layoutEnabled) {
        $classFragments[] = 'layout-disabled';
    }
    if (is_string($layoutMode) && $layoutMode !== '') {
        $classFragments[] = 'layout-mode-' . preg_replace('/[^a-z0-9-]/', '-', strtolower($layoutMode));
    }
    if (is_string($layoutVariant) && $layoutVariant !== '') {
        $classFragments[] = 'layout-variant-' . preg_replace('/[^a-z0-9-]/', '-', strtolower($layoutVariant));
    }
    $bodyClass = htmlspecialchars(implode(' ', $classFragments));

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
    echo '<script src="/assets/js/global/register_upload_inputs.js" defer></script>';
    echo '<script src="/assets/js/global/register_embed_observer.js" defer></script>';
    echo '<script src="/assets/js/global/register_dataset_viewer.js" defer></script>';
    echo '<script src="/assets/js/global/init_client.js" defer></script>';
    echo $extra_head;
    echo '</head>';
    echo '<body class="' . $bodyClass . '" data-embed-policy="' . htmlspecialchars($embed_policy) . '" data-statistics-visibility="' . htmlspecialchars($statistics_policy) . '" data-layout-mode="' . htmlspecialchars((string) $layoutMode) . '" data-layout-variant="' . htmlspecialchars((string) $layoutVariant) . '" data-layout-enabled="' . ($layoutEnabled ? 'true' : 'false') . '">';
    echo '<header class="app-header">';
    echo '<div class="app-title">' . htmlspecialchars($app_name) . '</div>';
    if ($nav) {
        echo '<nav class="app-nav">';
        if ($current) {
            echo '<a href="/index.php">Feed</a>';
            echo '<a href="/profile.php?user=' . urlencode($current['username']) . '">My Profile</a>';
            echo '<a href="/settings.php">Settings</a>';
            if (fg_is_admin($current)) {
                echo '<a href="/setup.php">Setup</a>';
            }
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

