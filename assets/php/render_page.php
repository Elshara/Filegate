<?php

function fg_render_page(array $page, array $options = []): string
{
    $template = $page['template'] ?? 'standard';
    $classes = ['page-view', 'page-template-' . preg_replace('/[^a-z0-9-]/', '-', strtolower((string) $template))];
    $header = '<header class="page-header">';
    $header .= '<h1>' . htmlspecialchars($page['title'] ?? 'Page') . '</h1>';
    if (!empty($page['summary'])) {
        $header .= '<p class="page-summary">' . htmlspecialchars($page['summary']) . '</p>';
    }
    $header .= '</header>';

    $format = $page['format'] ?? 'html';
    $bodyContent = (string) ($page['content'] ?? '');
    if ($format === 'text') {
        $bodyContent = nl2br(htmlspecialchars($bodyContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    $body = '<div class="page-content" data-format="' . htmlspecialchars($format) . '">' . $bodyContent . '</div>';

    $metaPieces = [];
    if (!empty($page['updated_at'])) {
        $metaPieces[] = 'Updated ' . htmlspecialchars(date('F j, Y', strtotime($page['updated_at'])));
    }
    if (!empty($page['visibility'])) {
        $metaPieces[] = 'Visibility: ' . htmlspecialchars(ucfirst((string) $page['visibility']));
    }

    $footer = '';
    if (!empty($metaPieces)) {
        $footer = '<footer class="page-footer">' . implode(' · ', $metaPieces) . '</footer>';
    }

    $article = '<article class="' . implode(' ', $classes) . '">' . $header . $body . $footer . '</article>';

    if (!empty($options['wrap'])) {
        return '<section class="page-container">' . $article . '</section>';
    }

    return $article;
}

