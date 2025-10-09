<?php

function fg_normalize_knowledge_slug(string $input): string
{
    $slug = strtolower(trim($input));
    $slug = preg_replace('/[^a-z0-9\-]+/i', '-', $slug);
    $slug = trim((string) $slug, '-');

    if ($slug === '') {
        $slug = 'article-' . substr(sha1($input . microtime()), 0, 8);
    }

    return $slug;
}
