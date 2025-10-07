<?php

function fg_format_file_size(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    $units = ['KB', 'MB', 'GB', 'TB'];
    $value = $bytes;
    $unit = 'B';

    foreach ($units as $candidate) {
        $value /= 1024;
        $unit = $candidate;
        if ($value < 1024) {
            break;
        }
    }

    return number_format($value, $value >= 10 ? 0 : 1) . ' ' . $unit;
}

