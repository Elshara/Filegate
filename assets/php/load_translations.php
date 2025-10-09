<?php

require_once __DIR__ . '/load_json.php';
require_once __DIR__ . '/default_translations_dataset.php';

function fg_load_translations(): array
{
    $defaults = fg_default_translations_dataset();
    $loaded = fg_load_json('translations');

    $tokens = $defaults['tokens'];
    if (isset($loaded['tokens']) && is_array($loaded['tokens'])) {
        $tokens = array_merge($tokens, $loaded['tokens']);
    }
    foreach ($tokens as $tokenKey => $definition) {
        if (!is_array($definition)) {
            $definition = [];
        }
        $label = $definition['label'] ?? ($defaults['tokens'][$tokenKey]['label'] ?? ucwords(str_replace(['.', '_', '-'], ' ', (string) $tokenKey)));
        $description = $definition['description'] ?? ($defaults['tokens'][$tokenKey]['description'] ?? '');
        $tokens[$tokenKey] = [
            'label' => (string) $label,
            'description' => (string) $description,
        ];
    }
    ksort($tokens);
    $loaded['tokens'] = $tokens;

    if (!isset($loaded['locales']) || !is_array($loaded['locales']) || empty($loaded['locales'])) {
        $loaded['locales'] = $defaults['locales'];
        $loaded['fallback_locale'] = $defaults['fallback_locale'];
    }

    if (!isset($loaded['fallback_locale']) || !is_string($loaded['fallback_locale']) || $loaded['fallback_locale'] === '') {
        $loaded['fallback_locale'] = $defaults['fallback_locale'];
    }

    foreach ($loaded['locales'] as $key => $definition) {
        if (!isset($definition['strings']) || !is_array($definition['strings'])) {
            $definition['strings'] = [];
        }
        $loaded['locales'][$key]['strings'] = $definition['strings'];
        if (!isset($definition['label']) || !is_string($definition['label'])) {
            $loaded['locales'][$key]['label'] = $key;
        }
    }

    return $loaded;
}

