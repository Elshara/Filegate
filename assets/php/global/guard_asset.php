<?php

require_once __DIR__ . '/get_asset_parameter_value.php';

function fg_guard_asset(string $asset, array $context = []): void
{
    $enabled = fg_get_asset_parameter_value($asset, 'enabled', $context);
    if ($enabled) {
        return;
    }

    $mode = fg_get_asset_parameter_value($asset, 'mode', $context);
    $variant = fg_get_asset_parameter_value($asset, 'variant', $context);

    http_response_code(503);
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head><meta charset="utf-8"><title>Asset disabled</title>';
    echo '<style>body{font-family:system-ui,sans-serif;background:#0f172a;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}';
    echo '.notice{max-width:520px;padding:2rem;background:rgba(15,23,42,0.85);border-radius:1rem;box-shadow:0 18px 40px rgba(0,0,0,0.35);}';
    echo '.notice h1{margin-top:0;font-size:1.75rem;}';
    echo '.notice p{line-height:1.5;font-size:1rem;color:rgba(255,255,255,0.85);}';
    echo '.notice code{background:rgba(255,255,255,0.12);padding:0.15rem 0.4rem;border-radius:0.5rem;font-size:0.9rem;display:inline-block;margin-top:0.5rem;}'
         . '</style></head>';
    echo '<body><div class="notice">';
    echo '<h1>Temporarily unavailable</h1>';
    echo '<p>The requested asset <code>' . htmlspecialchars($asset) . '</code> is currently disabled by configuration.</p>';
    if (is_string($mode) && $mode !== '') {
        echo '<p>Requested mode: <code>' . htmlspecialchars($mode) . '</code></p>';
    }
    if (is_string($variant) && $variant !== '') {
        echo '<p>Variant hint: <code>' . htmlspecialchars($variant) . '</code></p>';
    }
    echo '<p>Please contact an administrator to re-enable it or adjust your personal overrides.</p>';
    echo '</div></body></html>';
    exit;
}
