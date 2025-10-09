<?php

require_once __DIR__ . '/load_users.php';
require_once __DIR__ . '/save_users.php';
require_once __DIR__ . '/load_theme_tokens.php';
require_once __DIR__ . '/load_themes.php';

function fg_update_user_theme(array $user, string $themeKey, array $overrides = []): array
{
    $users = fg_load_users();
    $records = $users['records'] ?? [];
    $tokenDefinitions = fg_load_theme_tokens()['tokens'] ?? [];
    $themes = fg_load_themes()['records'] ?? [];

    if (!isset($themes[$themeKey])) {
        throw new RuntimeException('Selected theme is not available.');
    }

    $sanitisedOverrides = [];
    foreach ($overrides as $key => $value) {
        if (!isset($tokenDefinitions[$key])) {
            continue;
        }
        $type = $tokenDefinitions[$key]['type'] ?? 'text';
        $valueString = (string) $value;
        if ($type === 'color') {
            if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $valueString)) {
                continue;
            }
        } else {
            $valueString = trim($valueString);
        }
        $sanitisedOverrides[$key] = $valueString;
    }

    foreach ($records as $index => $existing) {
        if ((int) ($existing['id'] ?? 0) === (int) ($user['id'] ?? 0)) {
            $existing['theme_preferences'] = [
                'theme' => $themeKey,
                'tokens' => $sanitisedOverrides,
            ];
            $records[$index] = $existing;
            $user = $existing;
            break;
        }
    }

    $users['records'] = $records;
    fg_save_users($users);

    return $user;
}

