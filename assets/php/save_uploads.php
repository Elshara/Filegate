<?php

require_once __DIR__ . '/save_json.php';

function fg_save_uploads(array $uploads): void
{
    fg_save_json('uploads', $uploads);
}

