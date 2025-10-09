<?php

function fg_render_embed(array $embed): string
{
    $class = htmlspecialchars($embed['class'] ?? 'embed-fragment');
    $label = htmlspecialchars($embed['label'] ?? 'Embed');
    $type = htmlspecialchars($embed['type'] ?? 'external');
    $html = $embed['html'] ?? '';
    $link = htmlspecialchars($embed['url'] ?? '');

    if ($html === '') {
        return '<div class="embed-fragment ' . $class . '"><a href="' . $link . '" rel="noopener" target="_blank">' . $label . '</a></div>';
    }

    return '<figure class="embed-fragment ' . $class . '" data-embed-type="' . $type . '"><div class="embed-media">' . $html . '</div><figcaption><a href="' . $link . '" rel="noopener" target="_blank">' . $label . '</a></figcaption></figure>';
}

