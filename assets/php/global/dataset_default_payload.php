<?php

require_once __DIR__ . '/dataset_format.php';
require_once __DIR__ . '/default_settings_dataset.php';
require_once __DIR__ . '/default_themes_dataset.php';
require_once __DIR__ . '/default_pages_dataset.php';
require_once __DIR__ . '/default_activity_log_dataset.php';

function fg_dataset_default_payload(string $name): ?string
{
    $format = fg_dataset_format($name);

    if ($format === 'json') {
        $defaults = null;
        switch ($name) {
            case 'users':
            case 'posts':
            case 'uploads':
            case 'notifications':
                $defaults = ['records' => [], 'next_id' => 1];
                break;
            case 'pages':
                $defaults = fg_default_pages_dataset();
                break;
            case 'asset_overrides':
                $defaults = ['records' => ['global' => [], 'roles' => [], 'users' => []]];
                break;
            case 'settings':
                $defaults = fg_default_settings_dataset();
                break;
            case 'themes':
                $defaults = fg_default_themes_dataset();
                break;
            case 'activity_log':
                $defaults = fg_default_activity_log_dataset();
                break;
            default:
                $defaults = null;
                break;
        }

        if ($defaults === null) {
            return null;
        }

        $encoded = json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            return null;
        }

        return $encoded . "\n";
    }

    return null;
}

