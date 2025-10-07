<?php

require_once __DIR__ . '/data_directory.php';

function fg_ensure_data_directory(): void
{
    $directory = fg_data_directory();
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to provision data directory.');
        }
    }
}

