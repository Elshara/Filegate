<?php

function fg_extract_urls(string $html): array
{
    $urls = [];

    $document = new DOMDocument();
    $internal = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($internal);
    if ($loaded) {
        foreach ($document->getElementsByTagName('*') as $node) {
            if ($node->hasAttribute('href')) {
                $urls[] = trim($node->getAttribute('href'));
            }
            if ($node->hasAttribute('src')) {
                $urls[] = trim($node->getAttribute('src'));
            }
        }
    }

    preg_match_all('/https?:\/\/[^\s"<]+/i', $html, $matches);
    if (!empty($matches[0])) {
        $urls = array_merge($urls, $matches[0]);
    }

    $urls = array_filter(array_map('trim', $urls), static function ($value) {
        if ($value === '') {
            return false;
        }
        $lower = strtolower($value);
        if (strpos($lower, 'javascript:') === 0) {
            return false;
        }
        if (strpos($lower, 'mailto:') === 0) {
            return false;
        }
        return true;
    });

    $unique = array_unique($urls);
    return array_values($unique);
}

