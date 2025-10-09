<?php

require_once __DIR__ . '/../global/bootstrap.php';
require_once __DIR__ . '/../global/current_user.php';
require_once __DIR__ . '/../global/guard_asset.php';
require_once __DIR__ . '/../global/filter_knowledge_articles.php';
require_once __DIR__ . '/../global/list_knowledge_categories.php';
require_once __DIR__ . '/../pages/render_knowledge_base.php';
require_once __DIR__ . '/../global/render_layout.php';

function fg_public_knowledge_controller(): void
{
    fg_bootstrap();
    $user = fg_current_user();
    fg_guard_asset('assets/php/public/knowledge_controller.php', [
        'role' => $user['role'] ?? null,
        'user_id' => $user['id'] ?? null,
    ]);

    $slug = isset($_GET['slug']) ? strtolower(trim((string) $_GET['slug'])) : '';
    $tag = isset($_GET['tag']) ? strtolower(trim((string) $_GET['tag'])) : null;
    if ($tag === '') {
        $tag = null;
    }

    $query = isset($_GET['q']) ? (string) $_GET['q'] : '';
    $query = trim($query);
    if ($query === '') {
        $query = null;
    }

    $categoryInput = isset($_GET['category']) ? strtolower(trim((string) $_GET['category'])) : '';
    $categoryId = null;
    $categorySlug = null;
    if ($categoryInput !== '') {
        $categories = fg_list_knowledge_categories($user ?? []);
        foreach ($categories as $category) {
            $slug = strtolower((string) ($category['slug'] ?? ''));
            if ($slug === $categoryInput) {
                $categoryId = (int) ($category['id'] ?? 0);
                $categorySlug = $slug;
                break;
            }
        }
    }

    $articles = fg_filter_knowledge_articles($user ?? [], [
        'tag' => $tag,
        'query' => $query,
        'category_id' => $categoryId,
    ]);
    $articleExists = false;
    if ($slug !== '') {
        foreach ($articles as $article) {
            if (strtolower((string) ($article['slug'] ?? '')) === $slug) {
                $articleExists = true;
                break;
            }
        }
    }

    if ($slug !== '' && !$articleExists) {
        http_response_code(404);
    }

    $body = fg_render_knowledge_base(
        $user ?? [],
        $slug !== '' ? $slug : null,
        $tag,
        $categorySlug,
        $query
    );
    $title = $slug !== '' ? 'Knowledge base entry' : 'Knowledge base';
    fg_render_layout($title, $body);
}
