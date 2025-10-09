<?php

require_once __DIR__ . '/../global/render_layout.php';
require_once __DIR__ . '/../global/load_translations.php';
require_once __DIR__ . '/../global/resolve_locale.php';
require_once __DIR__ . '/../global/get_setting.php';
require_once __DIR__ . '/../global/translate.php';

function fg_render_register_page(array $context = []): void
{
    $error = $context['error'] ?? '';
    $values = $context['values'] ?? [];
    $translations = fg_load_translations();
    $localeResolution = fg_resolve_locale(null);
    $activeLocale = $values['locale'] ?? $localeResolution['locale'];
    $localePolicy = fg_get_setting('locale_personalisation_policy', 'enabled');
    $availableLocales = $translations['locales'] ?? [];
    $heading = fg_translate('register.heading', [
        'locale' => $localeResolution['locale'],
        'fallback_locale' => $localeResolution['fallback_locale'],
        'default' => 'Create your profile',
    ]);
    $submitLabel = fg_translate('register.submit', [
        'locale' => $localeResolution['locale'],
        'fallback_locale' => $localeResolution['fallback_locale'],
        'default' => 'Create profile',
    ]);

    $body = '<section class="panel">';
    $body .= '<h1>' . htmlspecialchars($heading) . '</h1>';
    if ($error !== '') {
        $body .= '<p class="error">' . htmlspecialchars($error) . '</p>';
    }
    $body .= '<form method="post" action="/register.php">';
    $body .= '<label>Username<input type="text" name="username" value="' . htmlspecialchars($values['username'] ?? '') . '" required></label>';
    $body .= '<label>Password<input type="password" name="password" required></label>';
    $body .= '<label>Display name<input type="text" name="display_name" value="' . htmlspecialchars($values['display_name'] ?? '') . '" required></label>';
    $body .= '<label>Profile summary<textarea name="bio" placeholder="Share a bit about yourself">' . htmlspecialchars($values['bio'] ?? '') . '</textarea></label>';
    if ($localePolicy === 'enabled' && !empty($availableLocales)) {
        $body .= '<label>Preferred locale<select name="locale">';
        foreach ($availableLocales as $key => $definition) {
            $label = $definition['label'] ?? $key;
            $selected = ((string) $activeLocale === (string) $key) ? ' selected' : '';
            $body .= '<option value="' . htmlspecialchars((string) $key) . '"' . $selected . '>' . htmlspecialchars((string) $label) . '</option>';
        }
        $body .= '</select></label>';
    }
    $body .= '<button type="submit">' . htmlspecialchars($submitLabel) . '</button>';
    $body .= '</form>';
    $body .= '<p>Already have an account? <a href="/login.php">Sign in</a>.</p>';
    $body .= '</section>';

    fg_render_layout('Register', $body, ['nav' => false]);
}

