<?php

require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/current_user.php';
require_once __DIR__ . '/get_asset_parameter_value.php';
require_once __DIR__ . '/is_admin.php';
require_once __DIR__ . '/resolve_theme_tokens.php';
require_once __DIR__ . '/theme_inline_style.php';
require_once __DIR__ . '/pages_for_navigation.php';

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
    $theme = fg_resolve_theme_tokens($current);
    $themeStyle = fg_theme_inline_style($theme);
    $themeKey = $theme['theme_key'] ?? '';

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
    echo '<script src="/assets/js/global/register_theme_preview.js" defer></script>';
    echo '<script src="/assets/js/global/init_client.js" defer></script>';
    echo $themeStyle;
    echo $extra_head;
    echo '</head>';
    $bodyAttributes = sprintf(
        'class="%s" data-embed-policy="%s" data-statistics-visibility="%s" data-layout-mode="%s" data-layout-variant="%s" data-layout-enabled="%s" data-theme-key="%s"',
        $bodyClass,
        htmlspecialchars($embed_policy),
        htmlspecialchars($statistics_policy),
        htmlspecialchars((string) $layoutMode),
        htmlspecialchars((string) $layoutVariant),
        $layoutEnabled ? 'true' : 'false',
        htmlspecialchars((string) $themeKey)
    );
    echo '<body ' . $bodyAttributes . '>';
    $navPages = fg_pages_for_navigation($current);

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
            echo '<a href="/page.php">Pages</a>';
            foreach ($navPages as $navPage) {
                if (empty($navPage['slug'])) {
                    continue;
                }
                echo '<a href="/page.php?slug=' . urlencode((string) $navPage['slug']) . '">' . htmlspecialchars($navPage['title']) . '</a>';
            }
            echo '<form method="post" action="/logout.php" class="logout-form"><button type="submit">Sign out</button></form>';
        } else {
            echo '<a href="/login.php">Sign in</a>';
            echo '<a href="/register.php">Create account</a>';
            echo '<a href="/page.php">Pages</a>';
            foreach ($navPages as $navPage) {
                if (empty($navPage['slug'])) {
                    continue;
                }
                echo '<a href="/page.php?slug=' . urlencode((string) $navPage['slug']) . '">' . htmlspecialchars($navPage['title']) . '</a>';
            }
        }
        echo '</nav>';
    }
    echo '</header>';
    echo '<main class="app-main">' . $body . '</main>';
    echo '<script>document.addEventListener("DOMContentLoaded",function(){if(typeof window.fg_initClient==="function"){window.fg_initClient();}});</script>';
    echo '</body>';
    echo '</html>';
}

