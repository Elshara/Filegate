<?php

require_once __DIR__ . '/data_directory.php';

function fg_json_path(string $name): string
{
    return fg_data_directory() . '/' . $name . '.json';
}

