<?php

require_once __DIR__ . '/upload_directory.php';
require_once __DIR__ . '/load_uploads.php';
require_once __DIR__ . '/save_uploads.php';

function fg_store_upload(array $file, array $meta = []): ?array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmp = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmp)) {
        return null;
    }

    $original = (string) ($file['name'] ?? 'upload');
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $extension = $extension !== '' ? $extension : 'bin';
    $directory = fg_upload_directory($extension);
    $identifier = bin2hex(random_bytes(8));
    $filename = $identifier . '.' . $extension;
    $target = $directory . '/' . $filename;

    if (!move_uploaded_file($tmp, $target)) {
        return null;
    }

    $uploads = fg_load_uploads();
    $id = $uploads['next_id'] ?? 1;
    $uploads['next_id'] = $id + 1;

    $record = [
        'id' => $id,
        'identifier' => $identifier,
        'original_name' => $original,
        'extension' => $extension,
        'path' => $target,
        'size' => (int) ($file['size'] ?? 0),
        'mime_type' => (string) ($file['type'] ?? ''),
        'uploaded_at' => date(DATE_ATOM),
        'meta' => $meta,
    ];

    $uploads['records'][] = $record;
    fg_save_uploads($uploads);

    return $record;
}

