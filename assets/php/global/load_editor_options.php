<?php

require_once __DIR__ . '/load_json.php';

function fg_load_editor_options(): array
{
    $options = fg_load_json('editor_options');
    return is_array($options) ? $options : [];
}

