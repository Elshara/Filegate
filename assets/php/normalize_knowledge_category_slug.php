<?php

function fg_normalize_knowledge_category_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9\-]+/', '-', $value);
    $value = trim((string) $value, '-');
    if ($value === '') {
        $value = 'category-' . substr(md5((string) microtime(true)), 0, 6);
    }

    return $value;
}
