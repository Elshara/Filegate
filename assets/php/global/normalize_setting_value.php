<?php

function fg_normalize_setting_value(string $value)
{
    $trimmed = trim($value);
    if ($trimmed === '') {
        return '';
    }

    if (($trimmed[0] === '{' && substr($trimmed, -1) === '}') || ($trimmed[0] === '[' && substr($trimmed, -1) === ']')) {
        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }

    if (stripos($trimmed, 'true') === 0) {
        return true;
    }

    if (stripos($trimmed, 'false') === 0) {
        return false;
    }

    return $value;
}

