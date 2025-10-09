<?php

function fg_normalize_page_slug(string $slug): string
{
    $normalized = strtolower(trim($slug));
    $normalized = preg_replace('/[^a-z0-9-]+/', '-', $normalized);
    $normalized = trim((string) $normalized, '-');

    if ($normalized === '') {
        $normalized = 'page-' . substr(md5((string) microtime(true)), 0, 8);
    }

    return $normalized;
}

