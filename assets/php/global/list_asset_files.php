<?php

function fg_list_asset_files(): array
{
    $root = realpath(__DIR__ . '/../../..');
    if ($root === false) {
        return [];
    }

    $directories = [
        $root . '/assets',
        $root . '/public',
    ];

    $allowed_extensions = [
        'php', 'js', 'css', 'json', 'xml', 'xhtml', 'html', 'htm', 'txt', 'htaccess'
    ];

    $files = [];

    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            if ($file->isDir()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if ($extension !== '' && !in_array($extension, $allowed_extensions, true)) {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($root) + 1);
            if (strpos($relative, '.git/') === 0 || strpos($relative, '/.git/') !== false) {
                continue;
            }

            $files[] = str_replace('\\', '/', $relative);
        }
    }

    sort($files);

    return array_values(array_unique($files));
}
