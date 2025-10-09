<?php

require_once __DIR__ . '/save_json.php';

function fg_save_asset_overrides(array $overrides): void
{
    fg_save_json('asset_overrides', $overrides);
}
