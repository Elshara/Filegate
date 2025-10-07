<?php

require_once __DIR__ . '/load_embed_providers.php';

function fg_detect_embed(string $url): ?array
{
    $providers = fg_load_embed_providers();
    foreach ($providers as $key => $definition) {
        $pattern = $definition['pattern'] ?? '';
        if ($pattern === '') {
            continue;
        }
        if (@preg_match('/' . $pattern . '/i', '') === false) {
            continue;
        }
        if (preg_match('/' . $pattern . '/i', $url, $matches)) {
            $identifier = $matches[1] ?? $url;
            $template = $definition['template'] ?? '';
            $html = $template !== '' ? str_replace('{{id}}', htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8'), $template) : '';
            return [
                'provider' => $key,
                'label' => $definition['label'] ?? $key,
                'type' => $definition['type'] ?? 'external',
                'url' => $url,
                'html' => $html,
                'class' => $definition['class'] ?? 'embed-external',
            ];
        }
    }

    $path = parse_url($url, PHP_URL_PATH);
    $extension = strtolower(pathinfo(is_string($path) ? $path : '', PATHINFO_EXTENSION));
    if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'avif'], true)) {
        return [
            'provider' => 'image',
            'label' => 'Image',
            'type' => 'image',
            'url' => $url,
            'html' => '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="Embedded image" loading="lazy">',
            'class' => 'embed-image',
        ];
    }

    if (in_array($extension, ['mp3', 'ogg', 'wav', 'aac'], true)) {
        return [
            'provider' => 'audio',
            'label' => 'Audio',
            'type' => 'audio',
            'url' => $url,
            'html' => '<audio controls preload="metadata" src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></audio>',
            'class' => 'embed-audio',
        ];
    }

    if (in_array($extension, ['mp4', 'webm', 'ogv', 'mov'], true)) {
        return [
            'provider' => 'video',
            'label' => 'Video',
            'type' => 'video',
            'url' => $url,
            'html' => '<video controls preload="metadata"><source src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></video>',
            'class' => 'embed-video',
        ];
    }

    return null;
}

