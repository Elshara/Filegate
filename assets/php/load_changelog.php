<?php

require_once __DIR__ . '/load_json.php';

function fg_load_changelog(): array
{
    return fg_load_json('changelog');
}

