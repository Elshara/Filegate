<?php

require_once __DIR__ . '/save_json.php';

function fg_save_content_modules(array $modules): void
{
    fg_save_json('content_modules', $modules);
}
