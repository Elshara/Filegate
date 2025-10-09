<?php

require_once __DIR__ . '/load_translations.php';
require_once __DIR__ . '/resolve_locale.php';

function fg_translate(string $token, array $context = []): string
{
    $translations = fg_load_translations();
    $locale = $context['locale'] ?? null;
    $fallbackLocale = $context['fallback_locale'] ?? null;

    if ($locale === null) {
        $user = $context['user'] ?? null;
        $resolved = fg_resolve_locale(is_array($user) ? $user : null);
        $locale = $resolved['locale'];
        if ($fallbackLocale === null) {
            $fallbackLocale = $resolved['fallback_locale'];
        }
    }

    if ($fallbackLocale === null) {
        $fallbackLocale = $translations['fallback_locale'] ?? 'en';
    }

    $default = $context['default'] ?? $token;

    $locales = $translations['locales'] ?? [];
    if (isset($locales[$locale]['strings'][$token]) && $locales[$locale]['strings'][$token] !== '') {
        return (string) $locales[$locale]['strings'][$token];
    }

    if (isset($locales[$fallbackLocale]['strings'][$token]) && $locales[$fallbackLocale]['strings'][$token] !== '') {
        return (string) $locales[$fallbackLocale]['strings'][$token];
    }

    foreach ($locales as $definition) {
        if (isset($definition['strings'][$token]) && $definition['strings'][$token] !== '') {
            return (string) $definition['strings'][$token];
        }
    }

    return (string) $default;
}

