<?php

require_once __DIR__ . '/collect_embeds.php';
require_once __DIR__ . '/render_embed.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/calculate_post_statistics.php';
require_once __DIR__ . '/load_template_options.php';

function fg_render_post_body(array $post): string
{
    $content = $post['content'] ?? '';
    $embeds = $post['embeds'] ?? fg_collect_embeds($content);
    $statistics = $post['statistics'] ?? [];
    if ($statistics === [] && $content !== '') {
        $statistics = fg_calculate_post_statistics($content, $embeds);
    }
    $display_options = $post['display_options'] ?? ['show_statistics' => true, 'show_embeds' => true];
    $embed_policy = fg_get_setting('rich_embed_policy', 'enabled');
    $statistics_policy = fg_get_setting('statistics_visibility', 'public');
    $should_render_embeds = ($display_options['show_embeds'] ?? true) && $embed_policy !== 'disabled';
    $renderable_embeds = $should_render_embeds ? $embeds : [];

    $payload = htmlspecialchars(json_encode($renderable_embeds, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
    $html = '<div class="post-content" data-embeds="' . $payload . '" data-embeds-rendered="' . (!empty($renderable_embeds) ? 'true' : 'false') . '">';

    if (!empty($post['summary'])) {
        $html .= '<p class="post-summary">' . htmlspecialchars($post['summary']) . '</p>';
    }

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

    $attachments = $post['attachments'] ?? [];
    if (!empty($attachments)) {
        $html .= '<ul class="post-attachments" aria-label="Attachments">';
        foreach ($attachments as $attachment) {
            $label = $attachment['original_name'] ?? ('File #' . ($attachment['id'] ?? '?'));
            $id = isset($attachment['id']) ? (int) $attachment['id'] : 0;
            $html .= '<li><a href="/media.php?upload=' . $id . '" target="_blank" rel="noopener">' . htmlspecialchars($label) . '</a>';
            if (isset($attachment['size'])) {
                $html .= ' <small>(' . round(((int) $attachment['size']) / 1024, 1) . ' KB)</small>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
    }

    if (!empty($post['tags'])) {
        $html .= '<ul class="post-tags" aria-label="Tags">';
        foreach ($post['tags'] as $tag) {
            $html .= '<li>' . htmlspecialchars($tag) . '</li>';
        }
        $html .= '</ul>';
    }

    $templates = fg_load_template_options();
    $template_label = null;
    foreach ($templates as $template) {
        if (($template['name'] ?? '') === ($post['template'] ?? '')) {
            $template_label = $template['label'] ?? $template['name'];
            break;
        }
    }
    if ($template_label) {
        $html .= '<p class="post-template">Template: ' . htmlspecialchars($template_label) . '</p>';
    }

    if (!empty($statistics) && $statistics_policy !== 'hidden' && ($display_options['show_statistics'] ?? true)) {
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

