<?php

function fg_data_directory(string $nature = 'dynamic'): string
{
    $base = __DIR__ . '/../../json';
    $folder = in_array($nature, ['static', 'dynamic'], true) ? $nature : 'dynamic';
    return $base . '/' . $folder;
}

