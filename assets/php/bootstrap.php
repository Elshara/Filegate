<?php

require_once __DIR__ . '/start_session.php';
require_once __DIR__ . '/seed_defaults.php';
require_once __DIR__ . '/ensure_asset_configurations.php';
require_once __DIR__ . '/ensure_asset_overrides.php';

function fg_bootstrap(): void
{
    fg_start_session();
    fg_seed_defaults();
    fg_ensure_asset_configurations();
    fg_ensure_asset_overrides();
}
