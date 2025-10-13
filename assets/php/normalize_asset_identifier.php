<?php

function fg_normalize_asset_identifier(string $asset): string
{
    $normalized = str_replace('\\', '/', $asset);

    $replacements = [
        'assets/css/global/' => 'assets/css/',
        'assets/js/global/' => 'assets/js/',
        'assets/php/global/' => 'assets/php/',
        'assets/php/public/' => 'assets/php/',
        'assets/php/pages/' => 'assets/php/',
        'assets/json/static/' => 'assets/json/',
        'assets/json/dynamic/' => 'assets/json/',
        'assets/xml/static/' => 'assets/xml/',
        'assets/xml/dynamic/' => 'assets/xml/',
    ];

    foreach ($replacements as $from => $to) {
        if (strpos($normalized, $from) === 0) {
            $normalized = $to . substr($normalized, strlen($from));
        }
    }

    if ($normalized === 'assets/css/style.css') {
        $normalized = 'assets/css/main.css';
    }

    return $normalized;
}
