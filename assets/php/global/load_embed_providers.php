<?php

require_once __DIR__ . '/load_json.php';

function fg_load_embed_providers(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $providers = fg_load_json('embed_providers');
    if (!is_array($providers)) {
        $providers = [];
    }

    return $cache = $providers;
}

