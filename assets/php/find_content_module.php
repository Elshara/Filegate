<?php

require_once __DIR__ . '/list_content_modules.php';
require_once __DIR__ . '/normalize_content_module_key.php';

function fg_find_content_module($identifier, ?string $dataset = null, array $options = []): ?array
{
    if ($identifier === null) {
        return null;
    }

    $modules = fg_list_content_modules($dataset, $options);
    if ($modules === []) {
        return null;
    }

    if (is_array($identifier) && isset($identifier['key'])) {
        $key = fg_normalize_content_module_key((string) $identifier['key']);
        if ($key !== '' && isset($modules[$key])) {
            return $modules[$key];
        }
    }

    $normalizedKey = fg_normalize_content_module_key((string) $identifier);
    if ($normalizedKey !== '' && isset($modules[$normalizedKey])) {
        return $modules[$normalizedKey];
    }

    if (is_numeric($identifier)) {
        $needle = (int) $identifier;
        foreach ($modules as $module) {
            if ((int) ($module['id'] ?? 0) === $needle) {
                return $module;
            }
        }
    }

    return null;
}
