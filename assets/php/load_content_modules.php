<?php

require_once __DIR__ . '/load_json.php';

function fg_load_content_modules(): array
{
    return fg_load_json('content_modules');
}
