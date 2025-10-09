<?php

require_once __DIR__ . '/save_json.php';

function fg_save_asset_configurations(array $configurations): void
{
    fg_save_json('asset_configurations', $configurations);
}
