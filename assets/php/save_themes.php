<?php

require_once __DIR__ . '/save_json.php';

function fg_save_themes(array $themes): void
{
    fg_save_json('themes', $themes);
}

