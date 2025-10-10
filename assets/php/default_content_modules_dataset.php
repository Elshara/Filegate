<?php

require_once __DIR__ . '/load_content_blueprints.php';
require_once __DIR__ . '/normalize_content_module_key.php';

function fg_default_content_modules_dataset(): array
{
    $blueprints = fg_load_content_blueprints();
    $categories = array_column($blueprints['categories'], 'title');
    $profilePrompts = array_map(static function (array $field): string {
        $name = trim((string) ($field['name'] ?? ''));
        $description = trim((string) ($field['description'] ?? ''));
        return $description === '' ? $name : $name . '|' . $description;
    }, array_slice($blueprints['profile_fields'], 0, 6));

    $cssTokens = array_slice($blueprints['css_values'], 0, 12);
    $moduleRecords = [];
    $nextId = 1;

    foreach ($blueprints['module_blueprints'] as $blueprint) {
        $title = trim((string) ($blueprint['title'] ?? ''));
        if ($title === '') {
            continue;
        }

        $keyBase = fg_normalize_content_module_key($title);
        if ($keyBase === '') {
            $keyBase = 'module';
        }

        $key = $keyBase;
        $suffix = 2;
        $existingKeys = array_map(static function (array $record): string {
            return (string) ($record['key'] ?? '');
        }, $moduleRecords);
        while (in_array($key, $existingKeys, true)) {
            $key = $keyBase . '-' . $suffix;
            $suffix++;
        }

        $fieldBlueprints = [];
        foreach ($blueprint['fields'] ?? [] as $field) {
            $fieldTitle = trim((string) ($field['title'] ?? ''));
            if ($fieldTitle === '') {
                continue;
            }
            $description = trim((string) ($field['description'] ?? ''));
            $fieldBlueprints[] = $fieldTitle . ($description !== '' ? '|' . $description : '');
        }

        if (empty($fieldBlueprints)) {
            $fieldBlueprints[] = $title . '|Describe how this entry should appear when published.';
        }

        $wizardSteps = [
            'Outline|' . ($blueprint['description'] ?? 'Describe the intent for this module.'),
            'Structure|Select categories and supporting fields before publishing.',
            'Review|Confirm attachments, collaborators, and privacy rules prior to launch.',
        ];

        $moduleRecords[] = [
            'id' => $nextId,
            'key' => $key,
            'label' => $title,
            'format' => (string) ($blueprint['format'] ?? ''),
            'dataset' => 'posts',
            'description' => trim((string) ($blueprint['description'] ?? '')),
            'categories' => array_slice($categories, 0, 6),
            'fields' => $fieldBlueprints,
            'profile_prompts' => $profilePrompts,
            'wizard_steps' => $wizardSteps,
            'css_tokens' => $cssTokens,
            'guides' => [
                'micro' => [
                    [
                        'title' => 'Identify the goal',
                        'prompt' => 'Summarise what this module should help members publish.',
                    ],
                    [
                        'title' => 'Surface references',
                        'prompt' => 'Link to categories, profile prompts, or assets members should review while drafting.',
                    ],
                    [
                        'title' => 'Highlight next steps',
                        'prompt' => 'Explain where published entries should appear and who should maintain them.',
                    ],
                ],
                'macro' => [
                    [
                        'title' => 'Plan the workflow',
                        'prompt' => 'Outline how this module connects to other datasets or follow-up actions.',
                    ],
                    [
                        'title' => 'Coordinate roles',
                        'prompt' => 'Describe which roles steward the module and how they collaborate during reviews.',
                    ],
                    [
                        'title' => 'Measure success',
                        'prompt' => 'List the signals or datasets to monitor once entries go live.',
                    ],
                ],
            ],
            'relationships' => [],
            'status' => 'active',
            'visibility' => 'members',
            'allowed_roles' => [],
        ];

        $nextId++;
        if ($nextId > 8) {
            break;
        }
    }

    return [
        'records' => $moduleRecords,
        'next_id' => $nextId,
        'metadata' => [
            'blueprint_count' => count($blueprints['module_blueprints']),
            'category_count' => count($categories),
            'profile_prompt_count' => count($blueprints['profile_fields']),
        ],
    ];
}
