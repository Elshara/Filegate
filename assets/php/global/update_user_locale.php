<?php

require_once __DIR__ . '/load_translations.php';
require_once __DIR__ . '/upsert_user.php';

function fg_update_user_locale(array $user, string $locale): array
{
    $translations = fg_load_translations();
    $locales = $translations['locales'] ?? [];
    $normalized = trim($locale);

    if ($normalized === '' || !isset($locales[$normalized])) {
        unset($user['locale']);
    } else {
        $user['locale'] = $normalized;
    }

    return fg_upsert_user($user);
}

