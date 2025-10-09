<?php

require_once __DIR__ . '/data_directory.php';

function fg_ensure_data_directory(): void
{
    $paths = [];
    foreach (['json', 'xml'] as $format) {
        foreach (['static', 'dynamic'] as $nature) {
            $paths[] = fg_data_directory($nature, $format);
        }
    }

    $paths[] = dirname(fg_data_directory('dynamic', 'json'));
    $paths[] = __DIR__ . '/../../uploads';

    foreach ($paths as $directory) {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to provision data directory.');
            }
        }
    }
}

