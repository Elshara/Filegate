<?php

require_once __DIR__ . '/../global/filter_knowledge_articles.php';
require_once __DIR__ . '/../global/sanitize_html.php';
require_once __DIR__ . '/../global/translate.php';
require_once __DIR__ . '/../global/get_asset_parameter_value.php';
require_once __DIR__ . '/../global/list_knowledge_categories.php';

function fg_render_knowledge_base(
    array $viewer,
    ?string $slug = null,
    ?string $tag = null,
    ?string $categorySlug = null,
    ?string $query = null
): string
{
    $context = [
        'role' => $viewer['role'] ?? null,
        'user_id' => $viewer['id'] ?? null,
    ];
    $enabled = fg_get_asset_parameter_value('assets/php/pages/render_knowledge_base.php', 'enabled', $context);
    if (!$enabled) {
        return '<p class="notice error">The knowledge base is currently disabled.</p>';
    }

    $defaultTagFilter = trim((string) fg_get_asset_parameter_value('assets/php/pages/render_knowledge_base.php', 'default_tag', $context));
    $defaultCategorySlug = trim((string) fg_get_asset_parameter_value('assets/php/pages/render_knowledge_base.php', 'default_category', $context));
    $showTagCloud = fg_get_asset_parameter_value('assets/php/pages/render_knowledge_base.php', 'show_tag_cloud', $context);
    $listingOverride = (int) fg_get_asset_parameter_value('assets/php/pages/render_knowledge_base.php', 'listing_limit', $context);
    $enableSearch = fg_get_asset_parameter_value('assets/php/pages/render_knowledge_base.php', 'enable_search', $context);
    $enableCategoryFilter = fg_get_asset_parameter_value('assets/php/pages/render_knowledge_base.php', 'enable_category_filter', $context);

    $categories = fg_list_knowledge_categories($viewer);
    $categoryIndex = [];
    foreach ($categories as $category) {
        $categoryIndex[(int) ($category['id'] ?? 0)] = $category;
    }

    $selectedCategoryId = null;
    if ($categorySlug !== null && $categorySlug !== '') {
        $categorySlug = strtolower($categorySlug);
        foreach ($categories as $category) {
            if (strtolower((string) ($category['slug'] ?? '')) === $categorySlug) {
                $selectedCategoryId = (int) ($category['id'] ?? 0);
                break;
            }
        }
    }

    if ($selectedCategoryId === null && $slug === null && $tag === null && $query === null && $defaultCategorySlug !== '') {
        $normalizedDefaultCategory = strtolower($defaultCategorySlug);
        foreach ($categories as $category) {
            if (strtolower((string) ($category['slug'] ?? '')) === $normalizedDefaultCategory) {
                $selectedCategoryId = (int) ($category['id'] ?? 0);
                $categorySlug = $normalizedDefaultCategory;
                break;
            }
        }
    }

    $articles = fg_filter_knowledge_articles($viewer, [
        'tag' => $tag,
        'category_id' => $selectedCategoryId,
        'query' => $query,
    ]);
    $heading = fg_translate('feed.knowledge.heading', ['user' => $viewer, 'default' => 'Knowledge base']);
    $role = strtolower((string) ($viewer['role'] ?? ''));
    $canModerate = in_array($role, ['admin', 'moderator'], true);

    $tag = $tag !== null ? strtolower(trim($tag)) : null;
    if ($tag === '') {
        $tag = null;
    }
    if ($tag === null && $slug === null && $defaultTagFilter !== '') {
        $tag = strtolower($defaultTagFilter);
    }

    $html = '<section class="panel knowledge-directory">';
    $html .= '<h1>' . htmlspecialchars($heading) . '</h1>';

    $activeFilters = [];
    if ($tag !== null) {
        $activeFilters[] = 'Tag <strong>' . htmlspecialchars($tag) . '</strong>';
    }
    if ($categorySlug !== null && $categorySlug !== '' && $selectedCategoryId !== null && isset($categoryIndex[$selectedCategoryId])) {
        $activeFilters[] = 'Category <strong>' . htmlspecialchars((string) ($categoryIndex[$selectedCategoryId]['name'] ?? '')) . '</strong>';
    }
    if ($query !== null && $query !== '') {
        $activeFilters[] = 'Query <strong>' . htmlspecialchars($query) . '</strong>';
    }
    if (!empty($activeFilters)) {
        $html .= '<p class="knowledge-tag-filter">Filtering by ' . implode(' · ', $activeFilters) . '. <a href="/knowledge.php">Clear filter</a></p>';
    }

    if ($slug !== null) {
        $article = null;
        foreach ($articles as $candidate) {
            if (strtolower((string) ($candidate['slug'] ?? '')) === strtolower($slug)) {
                $article = $candidate;
                break;
            }
        }

        if ($article === null) {
            $html .= '<p class="notice error">The requested article could not be found or you do not have permission to view it.</p>';
        } else {
            $updatedAt = (string) ($article['updated_at'] ?? $article['created_at'] ?? '');
            $updatedLabel = '';
            if ($updatedAt !== '') {
                $timestamp = strtotime($updatedAt);
                if ($timestamp) {
                    $updatedLabel = 'Updated ' . date('M j, Y', $timestamp);
                }
            }

            $status = strtolower((string) ($article['status'] ?? 'published'));
            $visibility = strtolower((string) ($article['visibility'] ?? 'public'));
            $tags = $article['tags'] ?? [];
            if (!is_array($tags)) {
                $tags = [];
            }

            $html .= '<article class="knowledge-article">';
            $html .= '<header class="knowledge-article-header">';
            $html .= '<h2>' . htmlspecialchars((string) ($article['title'] ?? 'Untitled article')) . '</h2>';
            if ($updatedLabel !== '') {
                $html .= '<p class="knowledge-article-updated">' . htmlspecialchars($updatedLabel) . '</p>';
            }
            if ($canModerate) {
                $html .= '<p class="knowledge-article-metadata">Status: ' . htmlspecialchars(ucwords(str_replace('_', ' ', $status))) . ' · Visibility: ' . htmlspecialchars(ucfirst($visibility)) . '</p>';
            }
            $articleCategoryId = (int) ($article['category_id'] ?? 0);
            if ($articleCategoryId > 0 && isset($categoryIndex[$articleCategoryId])) {
                $html .= '<p class="knowledge-article-category">Category: <a href="/knowledge.php?category=' . urlencode(strtolower((string) ($categoryIndex[$articleCategoryId]['slug'] ?? ''))) . '">' . htmlspecialchars((string) ($categoryIndex[$articleCategoryId]['name'] ?? '')) . '</a></p>';
            }
            if (!empty($article['summary'])) {
                $html .= '<p class="knowledge-article-summary">' . htmlspecialchars((string) $article['summary']) . '</p>';
            }
            $html .= '</header>';

            $content = (string) ($article['content'] ?? '');
            if ($content !== '') {
                $html .= '<div class="knowledge-article-body">' . fg_sanitize_html($content) . '</div>';
            }

            if (!empty($tags)) {
                $html .= '<ul class="knowledge-article-tags">';
                foreach ($tags as $tagItem) {
                    $slugged = strtolower((string) $tagItem);
                    $html .= '<li><a href="/knowledge.php?tag=' . urlencode($slugged) . '">' . htmlspecialchars((string) $tagItem) . '</a></li>';
                }
                $html .= '</ul>';
            }

            if (!empty($article['attachments']) && is_array($article['attachments'])) {
                $html .= '<ul class="knowledge-article-attachments">';
                foreach ($article['attachments'] as $attachment) {
                    $path = (string) $attachment;
                    if ($path === '') {
                        continue;
                    }
                    $label = basename($path);
                    $html .= '<li><a href="' . htmlspecialchars($path) . '">' . htmlspecialchars($label) . '</a></li>';
                }
                $html .= '</ul>';
            }

            $html .= '<p class="knowledge-article-back"><a href="/knowledge.php">Back to knowledge base</a></p>';
            $html .= '</article>';
        }

        $html .= '</section>';
        return $html;
    }

    if ($slug === null && $listingOverride > 0) {
        $articles = array_slice($articles, 0, $listingOverride);
    }

    if (empty($articles)) {
        $html .= '<p class="notice muted">No knowledge base entries are available yet. Administrators can add one from the setup dashboard.</p>';
        $html .= '</section>';
        return $html;
    }

    $html .= '<form method="get" action="/knowledge.php" class="knowledge-filters">';
    $html .= '<div class="knowledge-filter-group">';
    $html .= '<label>Tag<input type="search" name="tag" value="' . htmlspecialchars((string) ($tag ?? '')) . '" placeholder="accessibility, onboarding..."></label>';
    if ($enableCategoryFilter && !empty($categories)) {
        $html .= '<label>Category<select name="category">';
        $html .= '<option value="">All categories</option>';
        foreach ($categories as $category) {
            $slugValue = strtolower((string) ($category['slug'] ?? ''));
            $selected = ($categorySlug !== null && $categorySlug === $slugValue) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($slugValue) . '"' . $selected . '>' . htmlspecialchars((string) ($category['name'] ?? '')) . '</option>';
        }
        $html .= '</select></label>';
    }
    if ($enableSearch) {
        $html .= '<label>Search<input type="search" name="q" value="' . htmlspecialchars((string) ($query ?? '')) . '" placeholder="Search articles"></label>';
    }
    $html .= '</div>';
    $html .= '<div class="knowledge-filter-actions">';
    $html .= '<button type="submit">Filter</button>';
    if (!empty($activeFilters)) {
        $html .= '<a class="button secondary" href="/knowledge.php">Clear</a>';
    }
    $html .= '</div>';
    $html .= '</form>';

    $html .= '<ul class="knowledge-list">';
    foreach ($articles as $article) {
        $title = trim((string) ($article['title'] ?? 'Untitled article'));
        $summary = trim((string) ($article['summary'] ?? ''));
        $status = strtolower((string) ($article['status'] ?? 'published'));
        $visibility = strtolower((string) ($article['visibility'] ?? 'public'));
        $tags = $article['tags'] ?? [];
        if (!is_array($tags)) {
            $tags = [];
        }
        $articleCategoryId = (int) ($article['category_id'] ?? 0);
        $updatedAt = (string) ($article['updated_at'] ?? $article['created_at'] ?? '');
        $updatedLabel = '';
        if ($updatedAt !== '') {
            $timestamp = strtotime($updatedAt);
            if ($timestamp) {
                $updatedLabel = date('M j, Y', $timestamp);
            }
        }

        $html .= '<li class="knowledge-card">';
        $html .= '<h2><a href="/knowledge.php?slug=' . urlencode((string) ($article['slug'] ?? '')) . '">' . htmlspecialchars($title) . '</a></h2>';
        if ($articleCategoryId > 0 && isset($categoryIndex[$articleCategoryId])) {
            $html .= '<p class="knowledge-card-category"><a href="/knowledge.php?category=' . urlencode(strtolower((string) ($categoryIndex[$articleCategoryId]['slug'] ?? ''))) . '">' . htmlspecialchars((string) ($categoryIndex[$articleCategoryId]['name'] ?? '')) . '</a></p>';
        }
        if ($summary !== '') {
            $html .= '<p>' . htmlspecialchars($summary) . '</p>';
        }
        $metaParts = [];
        if ($updatedLabel !== '') {
            $metaParts[] = 'Updated ' . $updatedLabel;
        }
        if ($canModerate) {
            $metaParts[] = 'Status: ' . ucwords(str_replace('_', ' ', $status));
            $metaParts[] = 'Visibility: ' . ucfirst($visibility);
        }
        if (!empty($metaParts)) {
            $html .= '<p class="knowledge-card-meta">' . htmlspecialchars(implode(' · ', $metaParts)) . '</p>';
        }
        if (!empty($tags)) {
            $html .= '<ul class="knowledge-card-tags">';
            foreach ($tags as $tagItem) {
                $slugged = strtolower((string) $tagItem);
                $html .= '<li><a href="/knowledge.php?tag=' . urlencode($slugged) . '">' . htmlspecialchars((string) $tagItem) . '</a></li>';
            }
            $html .= '</ul>';
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    $html .= '</section>';

    return $html;
}
