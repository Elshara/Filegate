<?php

function fg_calculate_post_statistics(string $html, array $embeds = []): array
{
    $text = trim(strip_tags($html));
    $word_count = $text === '' ? 0 : str_word_count($text);
    $character_count = $text === '' ? 0 : mb_strlen(preg_replace('/\s+/u', '', $text), 'UTF-8');
    $embed_count = count($embeds);
    $heading_count = preg_match_all('/<h[1-6][^>]*>/i', $html, $unused) ?: 0;

    return [
        'word_count' => $word_count,
        'character_count' => $character_count,
        'embed_count' => $embed_count,
        'heading_count' => $heading_count,
        'calculated_at' => date(DATE_ATOM),
    ];
}

