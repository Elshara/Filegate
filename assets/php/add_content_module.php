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

    $guideParser = static function (array $lines): array {
        $guides = [];
        foreach ($lines as $line) {
            $parts = explode('|', (string) $line, 2);
            $title = trim($parts[0] ?? '');
            if ($title === '') {
                continue;
            }
            $guides[] = [
                'title' => $title,
                'prompt' => trim($parts[1] ?? ''),
            ];
        }

        return $guides;
    };

    $microGuides = $guideParser($lineParser($attributes['micro_guides'] ?? []));
    $macroGuides = $guideParser($lineParser($attributes['macro_guides'] ?? []));

    if (empty($microGuides)) {
        $microGuides = [
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
        ];
    }

    if (empty($macroGuides)) {
        $macroGuides = [
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
        ];
    }

    $cssTokens = $lineParser($attributes['css_tokens'] ?? []);

    $status = strtolower(trim((string) ($attributes['status'] ?? 'active')));
    if (!in_array($status, ['active', 'draft', 'archived'], true)) {
        $status = 'active';
    }

    $visibility = strtolower(trim((string) ($attributes['visibility'] ?? 'members')));
    if (!in_array($visibility, ['everyone', 'members', 'admins'], true)) {
        $visibility = 'members';
    }

    $allowedRolesInput = $lineParser($attributes['allowed_roles'] ?? []);
    $allowedRoles = [];
    foreach ($allowedRolesInput as $roleLine) {
        $parts = preg_split('/[,]+/u', (string) $roleLine);
        if ($parts === false) {
            $parts = [$roleLine];
        }
        foreach ($parts as $part) {
            $role = strtolower(trim((string) $part));
            if ($role === '' || in_array($role, $allowedRoles, true)) {
                continue;
            }
            $allowedRoles[] = $role;
        }
    }

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
        'guides' => [
            'micro' => $microGuides,
            'macro' => $macroGuides,
        ],
        'status' => $status,
        'visibility' => $visibility,
        'allowed_roles' => $allowedRoles,
    ];

    $records[] = $module;
    $modules['records'] = $records;

    fg_save_content_modules($modules);

    return $module;
}
