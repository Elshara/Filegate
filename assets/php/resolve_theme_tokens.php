<?php

require_once __DIR__ . '/load_theme_tokens.php';
require_once __DIR__ . '/load_themes.php';
require_once __DIR__ . '/get_setting.php';

function fg_resolve_theme_tokens(?array $user = null): array
{
    $tokenDefinitions = fg_load_theme_tokens()['tokens'] ?? [];
    $themesData = fg_load_themes();
    $themeRecords = $themesData['records'] ?? [];
    $metadata = $themesData['metadata'] ?? [];

    $defaultThemeKey = is_string($metadata['default'] ?? '') ? $metadata['default'] : null;
    if ($defaultThemeKey === null || !isset($themeRecords[$defaultThemeKey])) {
        $keys = array_keys($themeRecords);
        $defaultThemeKey = $keys[0] ?? null;
    }

    $settingTheme = fg_get_setting('default_theme', $defaultThemeKey ?? '');
    if (!is_string($settingTheme) || !isset($themeRecords[$settingTheme])) {
        $settingTheme = $defaultThemeKey ?? '';
    }

    $activeThemeKey = $settingTheme;
    $userOverrides = [];
    if ($user !== null) {
        $preferences = $user['theme_preferences'] ?? [];
        if (is_array($preferences)) {
            $userTheme = $preferences['theme'] ?? '';
            if (is_string($userTheme) && isset($themeRecords[$userTheme])) {
                $activeThemeKey = $userTheme;
            }
            $userTokens = $preferences['tokens'] ?? [];
            if (is_array($userTokens)) {
                $userOverrides = $userTokens;
            }
        }
    }

    if (!isset($themeRecords[$activeThemeKey]) && $defaultThemeKey !== null && isset($themeRecords[$defaultThemeKey])) {
        $activeThemeKey = $defaultThemeKey;
    }

    $activeTheme = $themeRecords[$activeThemeKey] ?? [];
    $themeTokens = is_array($activeTheme['tokens'] ?? null) ? $activeTheme['tokens'] : [];

    $resolved = [];
    foreach ($tokenDefinitions as $key => $definition) {
        $defaultValue = $definition['default'] ?? '';
        $value = $defaultValue;
        if (isset($themeTokens[$key])) {
            $value = (string) $themeTokens[$key];
        }
        if (isset($userOverrides[$key]) && $userOverrides[$key] !== '') {
            $value = (string) $userOverrides[$key];
        }

        $resolved[$key] = [
            'value' => $value,
            'css_variable' => $definition['css_variable'] ?? ('--fg-' . str_replace('_', '-', $key)),
            'label' => $definition['label'] ?? ucfirst(str_replace('_', ' ', $key)),
            'description' => $definition['description'] ?? '',
            'type' => $definition['type'] ?? 'text',
        ];
    }

    return [
        'theme_key' => $activeThemeKey,
        'theme_label' => $activeTheme['label'] ?? $activeThemeKey,
        'theme_description' => $activeTheme['description'] ?? '',
        'tokens' => $resolved,
        'available_themes' => $themeRecords,
    ];
}

