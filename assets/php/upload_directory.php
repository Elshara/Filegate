<?php

require_once __DIR__ . '/ensure_data_directory.php';

function fg_upload_directory(string $extension): string
{
    fg_ensure_data_directory();
    $ext = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $extension)) ?: 'binary';
    $base = __DIR__ . '/../../uploads/' . $ext;
    if (!is_dir($base) && !mkdir($base, 0775, true) && !is_dir($base)) {
        throw new RuntimeException('Unable to provision upload directory.');
    }

    return $base;
}

