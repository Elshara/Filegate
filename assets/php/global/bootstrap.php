<?php

require_once __DIR__ . '/start_session.php';
require_once __DIR__ . '/seed_defaults.php';

function fg_bootstrap(): void
{
    fg_start_session();
    fg_seed_defaults();
}

