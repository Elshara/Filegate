<?php

require_once __DIR__ . '/dataset_path.php';

function fg_json_path(string $name): string
{
    return fg_dataset_path($name);
}

