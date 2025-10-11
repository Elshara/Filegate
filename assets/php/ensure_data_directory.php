<?php

require_once __DIR__ . '/data_directory.php';

function fg_ensure_data_directory(): void
{
    $paths = [];
    foreach (['json', 'xml'] as $format) {
        $base = fg_data_directory('dynamic', $format);
        $paths[] = $base;

        foreach (['static', 'dynamic'] as $nature) {
            $legacy = __DIR__ . '/../' . $format . '/' . $nature;
            if (is_dir($legacy)) {
                $paths[] = $legacy;
            }
        }
    }

    $paths[] = __DIR__ . '/../uploads';

    foreach ($paths as $directory) {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to provision data directory.');
            }
        }
    }
}

