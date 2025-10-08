<?php

require_once __DIR__ . '/load_json.php';

function fg_load_project_status(): array
{
    return fg_load_json('project_status');
}

