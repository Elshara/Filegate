<?php

function fg_asset_label(string $relative_path): string
{
    $normalized = str_replace('\\', '/', $relative_path);
    $trimmed = preg_replace('#^assets/#', '', $normalized);
    $trimmed = preg_replace('#^php/#', '', $trimmed);
    $without_ext = preg_replace('/\.[^.]+$/', '', $trimmed);
    $parts = preg_split('#[/_-]+#', $without_ext) ?: [];
    $parts = array_map('ucfirst', array_filter($parts, 'strlen'));
    if (empty($parts)) {
        return $relative_path;
    }
    return implode(' ', $parts);
}
