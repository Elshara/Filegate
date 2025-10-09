<?php

require_once __DIR__ . '/extract_urls.php';
require_once __DIR__ . '/detect_embed.php';

function fg_collect_embeds(string $html): array
{
    $urls = fg_extract_urls($html);
    if ($urls === []) {
        return [];
    }

    $embeds = [];
    foreach ($urls as $url) {
        $embed = fg_detect_embed($url);
        if ($embed !== null) {
            $embeds[] = $embed;
        }
    }

    return $embeds;
}

