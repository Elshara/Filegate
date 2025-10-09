<?php

require_once __DIR__ . '/../global/bootstrap.php';
require_once __DIR__ . '/../global/current_user.php';
require_once __DIR__ . '/../global/load_pages.php';
require_once __DIR__ . '/../global/filter_pages_for_user.php';
require_once __DIR__ . '/../global/can_view_page.php';
require_once __DIR__ . '/../global/find_page_by_slug.php';
require_once __DIR__ . '/../global/guard_asset.php';
require_once __DIR__ . '/../pages/render_page.php';
require_once __DIR__ . '/../pages/render_page_list.php';
require_once __DIR__ . '/../global/render_layout.php';

function fg_public_page_controller(): void
{
    fg_bootstrap();
    $user = fg_current_user();
    fg_guard_asset('assets/php/public/page_controller.php', [
        'role' => $user['role'] ?? null,
        'user_id' => $user['id'] ?? null,
    ]);

    $slug = isset($_GET['slug']) ? strtolower(trim((string) $_GET['slug'])) : '';
    $pages = fg_load_pages();
    $records = $pages['records'] ?? [];

    if ($slug === '') {
        $accessible = fg_filter_pages_for_user($records, $user);
        $body = fg_render_page_list($accessible);
        fg_render_layout('Pages', $body);
        return;
    }

    $page = fg_find_page_by_slug($slug, $pages);
    if ($page !== null) {
        if (!fg_can_view_page($page, $user)) {
            http_response_code(403);
            fg_render_layout('Access restricted', '<p class="notice error">You do not have permission to view this page.</p>');
            return;
        }
        $body = fg_render_page($page, ['wrap' => true]);
        fg_render_layout($page['title'] ?? 'Page', $body, ['head' => '']);
        return;
    }

    http_response_code(404);
    fg_render_layout('Page not found', '<p class="notice error">The requested page could not be located.</p>');
}

