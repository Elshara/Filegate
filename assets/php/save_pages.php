<?php

require_once __DIR__ . '/save_json.php';

function fg_save_pages(array $pages): void
{
    fg_save_json('pages', $pages);
}

