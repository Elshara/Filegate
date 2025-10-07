<?php

require_once __DIR__ . '/load_json.php';
require_once __DIR__ . '/json_path.php';

function fg_load_asset_mirrors(): array
{
    $path = fg_json_path('asset_mirrors');
    if (!file_exists($path)) {
        return [];
    }

    $data = fg_load_json($path);
    if (!is_array($data)) {
        return [];
    }

    $mirrors = $data['mirrors'] ?? [];
    if (!is_array($mirrors)) {
        return [];
    }

    $result = [];
    foreach ($mirrors as $mirror) {
        if (!is_array($mirror)) {
            continue;
        }
        $source = $mirror['source'] ?? null;
        $target = $mirror['target'] ?? null;
        if (!is_string($source) || $source === '' || !is_string($target) || $target === '') {
            continue;
        }
        $result[] = [
            'source' => str_replace('\\', '/', $source),
            'target' => str_replace('\\', '/', $target),
            'nature' => isset($mirror['nature']) && is_string($mirror['nature']) ? $mirror['nature'] : null,
            'scope' => isset($mirror['scope']) && is_string($mirror['scope']) ? $mirror['scope'] : null,
        ];
    }

    return $result;
}
