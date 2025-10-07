<?php

function fg_default_activity_log_dataset(): array
{
    return [
        'records' => [],
        'next_id' => 1,
        'metadata' => [
            'limit' => 500,
        ],
    ];
}
