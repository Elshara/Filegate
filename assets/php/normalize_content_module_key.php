<?php

function fg_normalize_content_module_key(string $label): string
{
    $normalized = strtolower(trim($label));
    if ($normalized === '') {
        return '';
    }

    $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
    $normalized = trim($normalized, '-');

    return $normalized;
}
