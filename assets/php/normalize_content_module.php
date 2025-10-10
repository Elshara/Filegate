<?php

require_once __DIR__ . '/normalize_content_module_key.php';

function fg_normalize_content_module_definition(array $module): array
{
    $normalized = $module;

    $keySource = $module['key'] ?? $module['label'] ?? '';
    $normalized['key'] = fg_normalize_content_module_key((string) $keySource);
    if ($normalized['key'] === '' && isset($module['id'])) {
        $normalized['key'] = 'module-' . (int) $module['id'];
    }
    if ($normalized['key'] === '') {
        $normalized['key'] = 'module-' . uniqid();
    }

    $labelSource = $module['label'] ?? '';
    if (trim((string) $labelSource) === '') {
        $labelSource = str_replace('-', ' ', $normalized['key']);
    }
    $normalized['label'] = trim((string) $labelSource);

    $normalized['dataset'] = trim((string) ($module['dataset'] ?? 'posts'));
    $normalized['description'] = trim((string) ($module['description'] ?? ''));
    $normalized['format'] = trim((string) ($module['format'] ?? ''));
    $normalized['stage'] = trim((string) ($module['stage'] ?? ''));

    $status = strtolower(trim((string) ($module['status'] ?? 'active')));
    if (!in_array($status, ['active', 'draft', 'archived'], true)) {
        $status = 'active';
    }
    $normalized['status'] = $status;

    $visibility = strtolower(trim((string) ($module['visibility'] ?? 'members')));
    if (!in_array($visibility, ['everyone', 'members', 'admins'], true)) {
        $visibility = 'members';
    }
    $normalized['visibility'] = $visibility;

    $allowedRolesRaw = $module['allowed_roles'] ?? [];
    if (is_string($allowedRolesRaw)) {
        $allowedRolesRaw = preg_split('/\R+/u', $allowedRolesRaw) ?: [];
    }
    if (!is_array($allowedRolesRaw)) {
        $allowedRolesRaw = [];
    }
    $allowedRoles = array_values(array_unique(array_filter(array_map(static function ($role) {
        return strtolower(trim((string) $role));
    }, $allowedRolesRaw), static function ($role) {
        return $role !== '';
    })));
    $normalized['allowed_roles'] = $allowedRoles;

    foreach (['categories', 'profile_prompts', 'wizard_steps', 'css_tokens'] as $listKey) {
        $values = $module[$listKey] ?? [];
        if (!is_array($values)) {
            $values = [];
        }
        $values = array_values(array_filter(array_map(static function ($value) {
            return is_string($value) ? trim($value) : '';
        }, $values), static function ($value) {
            return $value !== '';
        }));
        $normalized[$listKey] = $values;
    }

    $fields = $module['fields'] ?? [];
    if (!is_array($fields)) {
        $fields = [];
    }
    $normalizedFields = [];
    foreach ($fields as $index => $field) {
        if (is_string($field)) {
            $parts = explode('|', $field, 2);
            $label = trim($parts[0] ?? '');
            $prompt = trim($parts[1] ?? '');
            if ($label === '') {
                $label = 'Field ' . ($index + 1);
            }
            $key = fg_normalize_content_module_key($label);
            if ($key === '') {
                $key = 'field-' . ($index + 1);
            }
            $normalizedFields[] = [
                'key' => $key,
                'label' => $label,
                'prompt' => $prompt,
                'value' => '',
            ];
        } elseif (is_array($field)) {
            $label = trim((string) ($field['label'] ?? $field['title'] ?? ''));
            if ($label === '') {
                $label = 'Field ' . ($index + 1);
            }
            $keySource = $field['key'] ?? $label;
            $key = fg_normalize_content_module_key((string) $keySource);
            if ($key === '') {
                $key = 'field-' . ($index + 1);
            }
            $prompt = trim((string) ($field['prompt'] ?? $field['description'] ?? ''));
            $value = $field['value'] ?? '';
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $normalizedFields[] = [
                'key' => $key,
                'label' => $label,
                'prompt' => $prompt,
                'value' => is_string($value) ? trim($value) : '',
            ];
        }
    }
    $normalized['fields'] = $normalizedFields;

    return $normalized;
}
