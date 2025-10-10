<?php

require_once __DIR__ . '/load_content_modules.php';
require_once __DIR__ . '/save_content_modules.php';

function fg_update_content_module(int $moduleId, array $attributes): ?array
{
    if ($moduleId <= 0) {
        return null;
    }

    $modules = fg_load_content_modules();
    if (!isset($modules['records']) || !is_array($modules['records'])) {
        return null;
    }

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

    foreach ($modules['records'] as $index => $record) {
        if ((int) ($record['id'] ?? 0) !== $moduleId) {
            continue;
        }

        $label = trim((string) ($attributes['label'] ?? ($record['label'] ?? '')));
        if ($label === '') {
            $label = $record['label'] ?? 'Content module ' . $moduleId;
        }

        $dataset = trim((string) ($attributes['dataset'] ?? ($record['dataset'] ?? 'posts')));
        if ($dataset === '') {
            $dataset = 'posts';
        }

        $format = trim((string) ($attributes['format'] ?? ($record['format'] ?? '')));
        $description = trim((string) ($attributes['description'] ?? ($record['description'] ?? '')));

        $categories = $lineParser($attributes['categories'] ?? ($record['categories'] ?? []));

        $fieldsInput = $lineParser($attributes['fields'] ?? []);
        if (empty($fieldsInput) && isset($record['fields']) && is_array($record['fields'])) {
            $fieldsInput = array_map(static function ($field) {
                if (!is_array($field)) {
                    return '';
                }
                $label = trim((string) ($field['label'] ?? ''));
                $description = trim((string) ($field['description'] ?? ''));
                return $description === '' ? $label : $label . '|' . $description;
            }, $record['fields']);
        }

        $fields = [];
        foreach ($fieldsInput as $fieldLine) {
            $parts = explode('|', $fieldLine, 2);
            $fieldLabel = trim($parts[0] ?? '');
            if ($fieldLabel === '') {
                continue;
            }
            $fields[] = [
                'label' => $fieldLabel,
                'description' => trim($parts[1] ?? ''),
            ];
        }
        if (empty($fields) && isset($record['fields']) && is_array($record['fields'])) {
            $fields = $record['fields'];
        }

        $profileInput = $lineParser($attributes['profile_prompts'] ?? []);
        if (empty($profileInput) && isset($record['profile_prompts']) && is_array($record['profile_prompts'])) {
            $profileInput = array_map(static function ($prompt) {
                if (!is_array($prompt)) {
                    return '';
                }
                $label = trim((string) ($prompt['label'] ?? ''));
                $description = trim((string) ($prompt['description'] ?? ''));
                return $description === '' ? $label : $label . '|' . $description;
            }, $record['profile_prompts']);
        }

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
        if (empty($profilePrompts) && isset($record['profile_prompts']) && is_array($record['profile_prompts'])) {
            $profilePrompts = $record['profile_prompts'];
        }

        $wizardInput = $lineParser($attributes['wizard_steps'] ?? []);
        if (empty($wizardInput) && isset($record['wizard_steps']) && is_array($record['wizard_steps'])) {
            $wizardInput = array_map(static function ($step) {
                if (!is_array($step)) {
                    return '';
                }
                $title = trim((string) ($step['title'] ?? ''));
                $prompt = trim((string) ($step['prompt'] ?? ''));
                return $prompt === '' ? $title : $title . '|' . $prompt;
            }, $record['wizard_steps']);
        }

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
        if (empty($wizardSteps) && isset($record['wizard_steps']) && is_array($record['wizard_steps'])) {
            $wizardSteps = $record['wizard_steps'];
        }

        $cssTokens = $lineParser($attributes['css_tokens'] ?? []);
        if (empty($cssTokens) && isset($record['css_tokens']) && is_array($record['css_tokens'])) {
            $cssTokens = $record['css_tokens'];
        }

        $status = strtolower(trim((string) ($attributes['status'] ?? ($record['status'] ?? 'active'))));
        if (!in_array($status, ['active', 'draft', 'archived'], true)) {
            $status = strtolower(trim((string) ($record['status'] ?? 'active')));
            if (!in_array($status, ['active', 'draft', 'archived'], true)) {
                $status = 'active';
            }
        }

        $visibility = strtolower(trim((string) ($attributes['visibility'] ?? ($record['visibility'] ?? 'members'))));
        if (!in_array($visibility, ['everyone', 'members', 'admins'], true)) {
            $visibility = strtolower(trim((string) ($record['visibility'] ?? 'members')));
            if (!in_array($visibility, ['everyone', 'members', 'admins'], true)) {
                $visibility = 'members';
            }
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

        $modules['records'][$index] = [
            'id' => $moduleId,
            'key' => (string) ($record['key'] ?? ''),
            'label' => $label,
            'dataset' => $dataset,
            'format' => $format,
            'description' => $description,
            'categories' => $categories,
            'fields' => $fields,
            'profile_prompts' => $profilePrompts,
            'wizard_steps' => $wizardSteps,
            'css_tokens' => $cssTokens,
            'status' => $status,
            'visibility' => $visibility,
            'allowed_roles' => $allowedRoles,
        ];

        fg_save_content_modules($modules);

        return $modules['records'][$index];
    }

    return null;
}
