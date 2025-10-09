<?php

function fg_normalize_translation_token_key(string $tokenKey): string
{
    $normalized = strtolower(trim($tokenKey));
    if ($normalized === '') {
        return '';
    }

    $normalized = str_replace(['\\', '/'], '.', $normalized);
    $normalized = str_replace(' ', '.', $normalized);
    $normalized = preg_replace('/[^a-z0-9._-]/', '', $normalized) ?? '';
    $normalized = preg_replace('/\.{2,}/', '.', $normalized) ?? $normalized;
    $normalized = preg_replace('/_{2,}/', '_', $normalized) ?? $normalized;
    $normalized = trim($normalized, '._-');

    return $normalized;
}
