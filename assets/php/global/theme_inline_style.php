<?php

function fg_theme_inline_style(array $resolvedTokens): string
{
    $tokens = $resolvedTokens['tokens'] ?? [];
    if (empty($tokens)) {
        return '';
    }

    $variables = [];
    foreach ($tokens as $token) {
        if (!isset($token['css_variable'], $token['value'])) {
            continue;
        }
        $cssVariable = preg_replace('/[^a-z0-9\-]/i', '', (string) $token['css_variable']);
        $value = (string) $token['value'];
        $variables[] = $cssVariable . ':' . $value;
    }

    if (empty($variables)) {
        return '';
    }

    $surface = $tokens['surface']['value'] ?? '#111827';
    $surfaceAlt = $tokens['surface_alt']['value'] ?? $surface;
    $textPrimary = $tokens['text_primary']['value'] ?? '#f8fafc';
    $textSecondary = $tokens['text_secondary']['value'] ?? $textPrimary;
    $accent = $tokens['accent']['value'] ?? '#38bdf8';
    $accentOn = $tokens['accent_on']['value'] ?? '#041833';
    $border = $tokens['border']['value'] ?? 'rgba(255,255,255,0.1)';
    $positive = $tokens['positive']['value'] ?? '#22c55e';
    $warning = $tokens['warning']['value'] ?? '#f97316';
    $negative = $tokens['negative']['value'] ?? '#ef4444';

    $css = ':root{' . implode(';', $variables) . ';}';
    $css .= 'body{background:linear-gradient(180deg,' . $surfaceAlt . ',' . $surface . ');color:' . $textPrimary . ';}';
    $css .= '.app-header{background:' . $surfaceAlt . ';color:' . $textPrimary . ';}';
    $css .= '.app-nav a,.logout-form button{color:' . $textPrimary . ';border-color:' . $border . ';}';
    $css .= '.app-nav a:hover,.logout-form button:hover{background:' . $textPrimary . ';color:' . $surface . ';}';
    $css .= '.panel,.asset-card,.post-card,.setup-dataset-card,.theme-card{background:' . $surface . ';color:' . $textSecondary . ';border-color:' . $border . ';}';
    $css .= '.panel h1,.panel h2,.panel h3,.asset-card h2,.theme-card h3{color:' . $textPrimary . ';}';
    $css .= '.button.primary{background:' . $accent . ';color:' . $accentOn . ';border-color:' . $accent . ';}';
    $css .= '.button.primary:hover{filter:brightness(1.05);}';
    $css .= '.notice.success{background:rgba(34,197,94,0.15);border-color:' . $positive . ';color:' . $positive . ';}';
    $css .= '.notice.error{background:rgba(239,68,68,0.15);border-color:' . $negative . ';color:' . $negative . ';}';
    $css .= '.notice.warning{background:rgba(249,115,22,0.15);border-color:' . $warning . ';color:' . $warning . ';}';
    $css .= '.theme-preview{background:' . $surfaceAlt . ';color:' . $textPrimary . ';border-color:' . $border . ';}';
    $css .= '.theme-preview .accent{background:' . $accent . ';color:' . $accentOn . ';}';

    return '<style data-theme-tokens>' . $css . '</style>';
}

