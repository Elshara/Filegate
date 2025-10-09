<?php

require_once __DIR__ . '/save_json.php';

function fg_save_posts(array $posts): void
{
    fg_save_json('posts', $posts);
}

