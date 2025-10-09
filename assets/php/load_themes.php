<?php

require_once __DIR__ . '/load_json.php';

function fg_load_themes(): array
{
    $themes = fg_load_json('themes');
    return is_array($themes) ? $themes : ['records' => [], 'metadata' => []];
}

