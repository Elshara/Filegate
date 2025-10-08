<?php

require_once __DIR__ . '/load_translations.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/default_translations_dataset.php';

function fg_resolve_locale(?array $user = null): array
{
    $translations = fg_load_translations();
    $locales = $translations['locales'] ?? [];
    if (empty($locales)) {
        $defaults = fg_default_translations_dataset();
        $locales = $defaults['locales'];
        $translations['fallback_locale'] = $defaults['fallback_locale'];
    }

    $fallback = $translations['fallback_locale'] ?? 'en';
    if (!isset($locales[$fallback])) {
        $fallback = array_key_first($locales);
    }

    $defaultSetting = fg_get_setting('default_locale', $fallback);
    $preferred = $user['locale'] ?? null;

    $candidates = [];
    if (is_string($preferred) && $preferred !== '') {
        $candidates[] = $preferred;
    }
    if (is_string($defaultSetting) && $defaultSetting !== '') {
        $candidates[] = $defaultSetting;
    }
    if (is_string($fallback) && $fallback !== '') {
        $candidates[] = $fallback;
    }

    $resolved = null;
    foreach ($candidates as $candidate) {
        if (isset($locales[$candidate])) {
            $resolved = $candidate;
            break;
        }
    }

    if ($resolved === null) {
        $resolved = array_key_first($locales);
    }

    return [
        'locale' => $resolved,
        'fallback_locale' => $fallback,
        'locales' => $locales,
        'label' => $locales[$resolved]['label'] ?? $resolved,
    ];
}

