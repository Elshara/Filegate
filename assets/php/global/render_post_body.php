<?php

require_once __DIR__ . '/collect_embeds.php';
require_once __DIR__ . '/render_embed.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/calculate_post_statistics.php';

function fg_render_post_body(array $post): string
{
    $content = $post['content'] ?? '';
    $embeds = $post['embeds'] ?? fg_collect_embeds($content);
    $statistics = $post['statistics'] ?? [];
    if ($statistics === [] && $content !== '') {
        $statistics = fg_calculate_post_statistics($content, $embeds);
    }
    $embed_policy = fg_get_setting('rich_embed_policy', 'enabled');
    $statistics_policy = fg_get_setting('statistics_visibility', 'public');
    $renderable_embeds = $embed_policy === 'disabled' ? [] : $embeds;

    $payload = htmlspecialchars(json_encode($renderable_embeds, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
    $html = '<div class="post-content" data-embeds="' . $payload . '" data-embeds-rendered="' . (!empty($renderable_embeds) ? 'true' : 'false') . '">';

    if ($content !== '') {
        $html .= '<div class="post-body-text">' . $content . '</div>';
    }

    if (!empty($renderable_embeds)) {
        $html .= '<div class="post-embeds">';
        foreach ($renderable_embeds as $embed) {
            $html .= fg_render_embed($embed);
        }
        $html .= '</div>';
    }

    if (!empty($statistics) && $statistics_policy !== 'hidden') {
        $html .= '<dl class="post-statistics" aria-label="Post statistics">';
        if (isset($statistics['word_count'])) {
            $html .= '<div><dt>Words</dt><dd>' . (int) $statistics['word_count'] . '</dd></div>';
        }
        if (isset($statistics['character_count'])) {
            $html .= '<div><dt>Characters</dt><dd>' . (int) $statistics['character_count'] . '</dd></div>';
        }
        if (isset($statistics['embed_count'])) {
            $html .= '<div><dt>Embeds</dt><dd>' . (int) $statistics['embed_count'] . '</dd></div>';
        }
        if (isset($statistics['heading_count'])) {
            $html .= '<div><dt>Headings</dt><dd>' . (int) $statistics['heading_count'] . '</dd></div>';
        }
        $html .= '</dl>';
    }

    $html .= '</div>';

    return $html;
}

