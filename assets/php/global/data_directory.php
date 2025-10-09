<?php

function fg_data_directory(string $nature = 'dynamic', string $format = 'json'): string
{
    $nature = in_array($nature, ['static', 'dynamic'], true) ? $nature : 'dynamic';
    $format = in_array($format, ['json', 'xml'], true) ? $format : 'json';
    $base = __DIR__ . '/../../' . $format;

    return $base . '/' . $nature;
}

