<?php

require_once __DIR__ . '/load_content_modules.php';
require_once __DIR__ . '/save_content_modules.php';
require_once __DIR__ . '/normalize_content_module_key.php';

function fg_add_content_module(array $attributes): array
{
    $modules = fg_load_content_modules();
    if (!isset($modules['records']) || !is_array($modules['records'])) {
        $modules['records'] = [];
    }
    if (!isset($modules['next_id'])) {
        $modules['next_id'] = 1;
    }

    $records = $modules['records'];
    $id = (int) $modules['next_id'];
    $modules['next_id'] = $id + 1;

    $label = trim((string) ($attributes['label'] ?? ''));
    if ($label === '') {
        $label = 'Content module ' . $id;
    }

    $keyCandidate = (string) ($attributes['key'] ?? $label);
    $keyBase = fg_normalize_content_module_key($keyCandidate);
    if ($keyBase === '') {
        $keyBase = 'module';
    }

    $existingKeys = array_map(static function (array $record): string {
        return (string) ($record['key'] ?? '');
    }, $records);

    $key = $keyBase;
    $suffix = 2;
    while (in_array($key, $existingKeys, true)) {
        $key = $keyBase . '-' . $suffix;
        $suffix++;
    }

    $dataset = trim((string) ($attributes['dataset'] ?? 'posts'));
    if ($dataset === '') {
        $dataset = 'posts';
    }

    $format = trim((string) ($attributes['format'] ?? ''));
    $description = trim((string) ($attributes['description'] ?? ''));

    $lineParser = static function ($value): array {
        if (is_array($value)) {
            $lines = [];
            foreach ($value as $entry) {
                $lines = array_merge($lines, preg_split('/\R+/u', (string) $entry) ?: []);
            }
        } else {
            $lines = preg_split('/\R+/u', (string) $value) ?: [];
        }

        $lines = array_map(static function ($line) {
            return trim((string) $line);
        }, $lines);

        return array_values(array_filter($lines, static function ($line) {
            return $line !== '';
        }));
    };

    $categories = $lineParser($attributes['categories'] ?? []);

    $fieldsInput = $lineParser($attributes['fields'] ?? []);
    $fields = [];
    foreach ($fieldsInput as $fieldLine) {
        $parts = explode('|', $fieldLine, 2);
        $labelPart = trim($parts[0] ?? '');
        if ($labelPart === '') {
            continue;
        }
        $fields[] = [
            'label' => $labelPart,
            'description' => trim($parts[1] ?? ''),
        ];
    }

    $profileInput = $lineParser($attributes['profile_prompts'] ?? []);
    $profilePrompts = [];
    foreach ($profileInput as $profileLine) {
        $parts = explode('|', $profileLine, 2);
        $name = trim($parts[0] ?? '');
        if ($name === '') {
            continue;
        }
        $profilePrompts[] = [
            'label' => $name,
            'description' => trim($parts[1] ?? ''),
        ];
    }

    $wizardInput = $lineParser($attributes['wizard_steps'] ?? []);
    $wizardSteps = [];
    foreach ($wizardInput as $wizardLine) {
        $parts = explode('|', $wizardLine, 2);
        $titlePart = trim($parts[0] ?? '');
        if ($titlePart === '') {
            continue;
        }
        $wizardSteps[] = [
            'title' => $titlePart,
            'prompt' => trim($parts[1] ?? ''),
        ];
    }

    if (empty($wizardSteps)) {
        $wizardSteps[] = [
            'title' => 'Outline',
            'prompt' => 'Describe the intent for this module.',
        ];
        $wizardSteps[] = [
            'title' => 'Structure',
            'prompt' => 'List supporting fields and categories to include.',
        ];
        $wizardSteps[] = [
            'title' => 'Publish',
            'prompt' => 'Review access controls and launch when ready.',
        ];
    }

    $cssTokens = $lineParser($attributes['css_tokens'] ?? []);

    $module = [
        'id' => $id,
        'key' => $key,
        'label' => $label,
        'dataset' => $dataset,
        'format' => $format,
        'description' => $description,
        'categories' => $categories,
        'fields' => $fields,
        'profile_prompts' => $profilePrompts,
        'wizard_steps' => $wizardSteps,
        'css_tokens' => $cssTokens,
    ];

    $records[] = $module;
    $modules['records'] = $records;

    fg_save_content_modules($modules);

    return $module;
}
