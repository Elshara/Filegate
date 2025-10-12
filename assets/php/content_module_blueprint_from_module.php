<?php

require_once __DIR__ . '/normalize_content_module.php';

function fg_content_module_blueprint_from_module(array $module): array
{
    $normalized = fg_normalize_content_module_definition($module);

    $categories = [];
    foreach ($normalized['categories'] ?? [] as $category) {
        if (!is_string($category)) {
            continue;
        }
        $value = trim($category);
        if ($value !== '') {
            $categories[] = $value;
        }
    }

    $fields = [];
    foreach ($normalized['fields'] ?? [] as $field) {
        if (!is_array($field)) {
            continue;
        }
        $fields[] = [
            'key' => (string) ($field['key'] ?? ''),
            'label' => (string) ($field['label'] ?? ''),
            'prompt' => (string) ($field['prompt'] ?? ''),
        ];
    }

    $profilePrompts = [];
    $profileSource = $normalized['profile_prompts'] ?? [];
    if (!is_array($profileSource)) {
        $profileSource = [];
    }
    foreach ($profileSource as $index => $prompt) {
        if (is_string($prompt)) {
            $parts = explode('|', $prompt, 2);
            $label = trim($parts[0] ?? '');
            $description = trim($parts[1] ?? '');
        } elseif (is_array($prompt)) {
            $label = trim((string) ($prompt['label'] ?? $prompt['name'] ?? ''));
            $description = trim((string) ($prompt['description'] ?? ''));
        } else {
            $label = '';
            $description = '';
        }

        if ($label === '') {
            $label = 'Prompt ' . ($index + 1);
        }

        $profilePrompts[] = [
            'label' => $label,
            'description' => $description,
        ];
    }

    $wizardSteps = [];
    $wizardSource = $normalized['wizard_steps'] ?? [];
    if (!is_array($wizardSource)) {
        $wizardSource = [];
    }
    foreach ($wizardSource as $index => $step) {
        if (is_string($step)) {
            $parts = explode('|', $step, 2);
            $title = trim($parts[0] ?? '');
            $prompt = trim($parts[1] ?? '');
        } elseif (is_array($step)) {
            $title = trim((string) ($step['title'] ?? $step['label'] ?? ''));
            $prompt = trim((string) ($step['prompt'] ?? $step['description'] ?? ''));
        } else {
            $title = '';
            $prompt = '';
        }

        if ($title === '') {
            $title = 'Step ' . ($index + 1);
        }

        $wizardSteps[] = [
            'title' => $title,
            'prompt' => $prompt,
        ];
    }

    $guides = $normalized['guides'] ?? ['micro' => [], 'macro' => []];
    if (!is_array($guides)) {
        $guides = ['micro' => [], 'macro' => []];
    }
    $microGuides = [];
    foreach (($guides['micro'] ?? []) as $index => $guide) {
        if (!is_array($guide)) {
            continue;
        }
        $title = (string) ($guide['title'] ?? '');
        if ($title === '') {
            $title = 'Guide ' . ($index + 1);
        }
        $microGuides[] = [
            'title' => $title,
            'prompt' => (string) ($guide['prompt'] ?? ''),
        ];
    }
    $macroGuides = [];
    foreach (($guides['macro'] ?? []) as $index => $guide) {
        if (!is_array($guide)) {
            continue;
        }
        $title = (string) ($guide['title'] ?? '');
        if ($title === '') {
            $title = 'Guide ' . ($index + 1);
        }
        $macroGuides[] = [
            'title' => $title,
            'prompt' => (string) ($guide['prompt'] ?? ''),
        ];
    }

    $tasks = [];
    foreach ($normalized['tasks'] ?? [] as $task) {
        if (!is_array($task)) {
            continue;
        }
        $tasks[] = [
            'key' => (string) ($task['key'] ?? ''),
            'label' => (string) ($task['label'] ?? ''),
            'description' => (string) ($task['description'] ?? ''),
            'completed' => !empty($task['completed']),
            'owner' => (string) ($task['owner'] ?? ''),
            'due_date' => (string) ($task['due_date'] ?? ''),
            'priority' => (string) ($task['priority'] ?? ''),
            'priority_label' => (string) ($task['priority_label'] ?? ''),
            'notes' => (string) ($task['notes'] ?? ''),
            'weight' => isset($task['weight']) && is_numeric($task['weight']) ? (float) $task['weight'] : 1.0,
        ];
    }

    $relationships = [];
    foreach ($normalized['relationships'] ?? [] as $relationship) {
        if (!is_array($relationship)) {
            continue;
        }
        $relationships[] = [
            'type' => (string) ($relationship['type'] ?? 'related'),
            'module_key' => (string) ($relationship['module_key'] ?? ''),
            'module_label' => (string) ($relationship['module_label'] ?? ''),
            'description' => (string) ($relationship['description'] ?? ''),
        ];
    }

    $cssTokens = [];
    foreach ($normalized['css_tokens'] ?? [] as $token) {
        if (!is_string($token)) {
            continue;
        }
        $value = trim($token);
        if ($value !== '') {
            $cssTokens[] = $value;
        }
    }

    $allowedRoles = [];
    foreach ($normalized['allowed_roles'] ?? [] as $role) {
        if (!is_string($role)) {
            continue;
        }
        $value = strtolower(trim($role));
        if ($value !== '') {
            $allowedRoles[] = $value;
        }
    }

    return [
        'type' => 'content_module',
        'version' => 1,
        'generated_at' => gmdate('c'),
        'key' => (string) ($normalized['key'] ?? ''),
        'label' => (string) ($normalized['label'] ?? ''),
        'dataset' => (string) ($normalized['dataset'] ?? 'posts'),
        'format' => (string) ($normalized['format'] ?? ''),
        'description' => (string) ($normalized['description'] ?? ''),
        'status' => (string) ($normalized['status'] ?? ''),
        'visibility' => (string) ($normalized['visibility'] ?? ''),
        'allowed_roles' => $allowedRoles,
        'categories' => $categories,
        'fields' => $fields,
        'tasks' => $tasks,
        'profile_prompts' => $profilePrompts,
        'wizard_steps' => $wizardSteps,
        'guides' => [
            'micro' => $microGuides,
            'macro' => $macroGuides,
        ],
        'css_tokens' => $cssTokens,
        'relationships' => $relationships,
    ];
}

function fg_content_module_blueprint_json(array $module): string
{
    $blueprint = fg_content_module_blueprint_from_module($module);
    $encoded = json_encode($blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        return '';
    }

    return $encoded;
}
