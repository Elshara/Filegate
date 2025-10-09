<?php

require_once __DIR__ . '/save_json.php';

function fg_save_settings(array $settings): void
{
    fg_save_json('settings', $settings);
}

