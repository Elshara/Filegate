<?php

require_once __DIR__ . '/load_json.php';

function fg_load_template_options(): array
{
    $options = fg_load_json('template_options');
    return is_array($options) ? $options : [];
}

