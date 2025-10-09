<?php

require_once __DIR__ . '/load_json.php';

function fg_load_theme_tokens(): array
{
    $tokens = fg_load_json('theme_tokens');
    return is_array($tokens) ? $tokens : ['tokens' => []];
}

