<?php

require_once __DIR__ . '/normalize_asset_identifier.php';
require_once __DIR__ . '/load_asset_configurations.php';

function fg_asset_scope(string $relative_path): string
{
    static $configuredScopes;

    $normalized = fg_normalize_asset_identifier($relative_path);

    if ($configuredScopes === null) {
        $configuredScopes = [];
        $configurations = fg_load_asset_configurations();
        $records = $configurations['records'] ?? [];
        if (is_array($records)) {
            foreach ($records as $asset => $definition) {
                if (is_array($definition) && isset($definition['scope'])) {
                    $configuredScopes[fg_normalize_asset_identifier($asset)] = $definition['scope'];
                }
            }
        }
    }

    if (isset($configuredScopes[$normalized])) {
        return $configuredScopes[$normalized];
    }

    if (preg_match('/render_(feed|login|profile|register|settings|setup|page|page_list|knowledge_base)\.php$/', $normalized)) {
        return 'local';
    }

    return 'global';
}
