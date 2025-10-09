<?php

function fg_parse_collaborators(string $input): array
{
    $parts = preg_split('/\s*,\s*/', trim($input));
    $parts = array_filter($parts, static function ($part) {
        return $part !== '';
    });
    return array_values(array_unique($parts));
}

