<?php

require_once __DIR__ . '/normalize_content_module_key.php';
require_once __DIR__ . '/content_module_task_progress.php';

function fg_normalize_content_module_relationships($relationships): array
{
    $normalized = [];
    $seen = [];

    $register = static function (array $payload) use (&$normalized, &$seen): void {
        $type = strtolower(trim((string) ($payload['type'] ?? 'related')));
        if ($type === '') {
            $type = 'related';
        }

        $moduleKey = trim((string) ($payload['module_key'] ?? ''));
        if ($moduleKey === '') {
            return;
        }

        $dedupeKey = $type . '|' . $moduleKey;
        if (isset($seen[$dedupeKey])) {
            return;
        }
        $seen[$dedupeKey] = true;

        $moduleLabel = trim((string) ($payload['module_label'] ?? ''));
        $moduleReference = trim((string) ($payload['module_reference'] ?? ''));
        if ($moduleReference === '') {
            $moduleReference = $moduleLabel !== '' ? $moduleLabel : $moduleKey;
        }
        if ($moduleLabel === '') {
            $moduleLabel = $moduleReference !== '' ? $moduleReference : $moduleKey;
        }

        $normalized[] = [
            'type' => $type,
            'module_key' => $moduleKey,
            'module_label' => $moduleLabel,
            'module_reference' => $moduleReference,
            'description' => trim((string) ($payload['description'] ?? '')),
        ];
    };

    $processLine = static function (string $line) use (&$register): void {
        $line = trim($line);
        if ($line === '') {
            return;
        }

        $parts = explode('|', $line);
        $parts = array_map(static function ($part) {
            return trim((string) $part);
        }, $parts);

        $type = array_shift($parts);
        if ($type === null || $type === '') {
            $type = 'related';
        }

        $moduleReference = array_shift($parts);
        if (!is_string($moduleReference)) {
            return;
        }
        $moduleReference = trim($moduleReference);
        if ($moduleReference === '') {
            return;
        }

        $moduleLabel = '';
        $description = '';

        if (count($parts) === 1) {
            $description = trim((string) ($parts[0] ?? ''));
        } elseif (count($parts) >= 2) {
            $moduleLabel = trim((string) array_shift($parts));
            $description = trim(implode('|', $parts));
        }

        $moduleKey = fg_normalize_content_module_key($moduleReference);
        if ($moduleKey === '' && $moduleLabel !== '') {
            $moduleKey = fg_normalize_content_module_key($moduleLabel);
        }
        if ($moduleKey === '') {
            return;
        }

        $register([
            'type' => $type,
            'module_key' => $moduleKey,
            'module_label' => $moduleLabel,
            'module_reference' => $moduleReference,
            'description' => $description,
        ]);
    };

    if (is_array($relationships)) {
        foreach ($relationships as $entry) {
            if (is_string($entry)) {
                $processLine($entry);
                continue;
            }

            if (!is_array($entry)) {
                continue;
            }

            if (isset($entry['line']) && is_string($entry['line'])) {
                $processLine($entry['line']);
                continue;
            }

            $type = $entry['type'] ?? $entry['relationship'] ?? $entry['kind'] ?? 'related';
            $moduleReference = $entry['module_reference'] ?? $entry['module_label'] ?? $entry['label'] ?? '';
            $moduleKeySource = $entry['module_key'] ?? $entry['module'] ?? $entry['key'] ?? $entry['target'] ?? '';
            if (!is_string($moduleKeySource) || trim($moduleKeySource) === '') {
                $moduleKeySource = $moduleReference;
            }
            $moduleKey = fg_normalize_content_module_key((string) $moduleKeySource);
            if ($moduleKey === '' && is_string($moduleReference) && trim($moduleReference) !== '') {
                $moduleKey = fg_normalize_content_module_key((string) $moduleReference);
            }
            if ($moduleKey === '') {
                continue;
            }

            $register([
                'type' => $type,
                'module_key' => $moduleKey,
                'module_label' => $entry['module_label'] ?? $entry['label'] ?? $moduleReference ?? '',
                'module_reference' => $moduleReference ?? $moduleKeySource,
                'description' => $entry['description'] ?? $entry['notes'] ?? $entry['summary'] ?? '',
            ]);
        }
    } else {
        $lines = preg_split('/\R+/u', (string) $relationships) ?: [];
        foreach ($lines as $line) {
            $processLine($line);
        }
    }

    return $normalized;
}

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

    $taskSource = $module['tasks'] ?? ($module['checklists'] ?? []);
    if (is_string($taskSource)) {
        $taskSource = preg_split('/\R+/u', $taskSource) ?: [];
    }
    if (!is_array($taskSource)) {
        $taskSource = [];
    }

    $toBool = static function ($value): bool {
        if (is_bool($value)) {
            return $value;
        }

        $string = strtolower(trim((string) $value));
        if ($string === '') {
            return false;
        }

        return in_array($string, ['1', 'true', 'yes', 'y', 'on', 'complete', 'completed', 'done', 'finished', 'checked'], true);
    };

    $priorityAliases = [
        'std' => 'normal',
        'standard' => 'normal',
        'medium' => 'medium',
        'med' => 'medium',
        'mid' => 'medium',
        'moderate' => 'medium',
        'hi' => 'high',
        'high' => 'high',
        'urgent' => 'urgent',
        'critical' => 'critical',
        'blocker' => 'critical',
        'p1' => 'critical',
        'p2' => 'high',
        'p3' => 'medium',
        'p4' => 'low',
    ];

    $priorityLabels = [
        'low' => 'Low priority',
        'normal' => 'Normal priority',
        'medium' => 'Medium priority',
        'high' => 'High priority',
        'urgent' => 'Urgent priority',
        'critical' => 'Critical priority',
    ];

    $normalizedTasks = [];
    $taskKeyCounts = [];
    foreach ($taskSource as $index => $task) {
        $label = '';
        $description = '';
        $completed = false;
        $keyCandidate = '';
        $owner = '';
        $dueRaw = '';
        $dueTimestamp = null;
        $priorityRaw = '';
        $notes = '';
        $weight = 1.0;

        if (is_string($task)) {
            $parts = array_map(static function ($segment) {
                return trim((string) $segment);
            }, explode('|', $task));
            $label = $parts[0] ?? '';
            if ($label === '') {
                $label = 'Task ' . ($index + 1);
            }
            $description = $parts[1] ?? '';
            $statusHint = $parts[2] ?? '';
            if ($statusHint !== '') {
                $completed = $toBool($statusHint);
            }
            $owner = $parts[3] ?? '';
            $dueRaw = $parts[4] ?? '';
            $priorityRaw = $parts[5] ?? '';
            if (count($parts) > 6) {
                $notes = trim(implode('|', array_slice($parts, 6)));
            }
        } elseif (is_array($task)) {
            $label = trim((string) ($task['label'] ?? $task['title'] ?? ''));
            if ($label === '') {
                $label = 'Task ' . ($index + 1);
            }
            $description = trim((string) ($task['description'] ?? $task['prompt'] ?? $task['notes'] ?? ''));
            $keyCandidate = (string) ($task['key'] ?? $task['task_key'] ?? $task['identifier'] ?? '');
            if (isset($task['completed'])) {
                $completed = $toBool($task['completed']);
            } elseif (isset($task['default'])) {
                $completed = $toBool($task['default']);
            } elseif (isset($task['status'])) {
                $completed = $toBool($task['status']);
            } elseif (isset($task['state'])) {
                $completed = $toBool($task['state']);
            }
            $owner = trim((string) ($task['owner'] ?? $task['assignee'] ?? $task['assigned_to'] ?? ''));
            $dueRaw = trim((string) ($task['due_date'] ?? $task['due'] ?? $task['deadline'] ?? ''));
            if (isset($task['due_timestamp']) && is_numeric($task['due_timestamp'])) {
                $dueTimestamp = (int) $task['due_timestamp'];
            }
            $priorityRaw = trim((string) ($task['priority'] ?? $task['importance'] ?? ''));
            $notes = trim((string) ($task['notes'] ?? $task['note'] ?? ''));
            if (isset($task['weight'])) {
                $weight = (float) $task['weight'];
            } elseif (isset($task['points'])) {
                $weight = (float) $task['points'];
            } elseif (isset($task['value'])) {
                $weight = (float) $task['value'];
            }
        } else {
            continue;
        }

        if ($keyCandidate === '') {
            $keyCandidate = $label;
        }
        $key = fg_normalize_content_module_key($keyCandidate);
        if ($key === '') {
            $key = fg_normalize_content_module_key($label);
        }
        if ($key === '') {
            $key = 'task-' . ($index + 1);
        }

        if (isset($taskKeyCounts[$key])) {
            $taskKeyCounts[$key]++;
            $key .= '-' . $taskKeyCounts[$key];
        } else {
            $taskKeyCounts[$key] = 1;
        }

        if (!is_finite($weight) || $weight <= 0) {
            $weight = 1.0;
        }

        $owner = trim($owner);

        $priority = '';
        $priorityLabel = '';
        $priorityKey = strtolower(trim($priorityRaw));
        if ($priorityKey !== '') {
            $priorityKey = str_replace(['priority', 'prio', 'level'], '', $priorityKey);
            $priorityKey = trim($priorityKey);
            if ($priorityKey === '') {
                $priorityKey = strtolower(trim($priorityRaw));
            }
            if (isset($priorityAliases[$priorityKey])) {
                $priorityKey = $priorityAliases[$priorityKey];
            }
            if (isset($priorityLabels[$priorityKey])) {
                $priority = $priorityKey;
                $priorityLabel = $priorityLabels[$priorityKey];
            } elseif ($priorityRaw !== '') {
                $priority = trim((string) $priorityRaw);
                $priorityLabel = ucfirst($priority);
            }
        }

        $dueDate = '';
        $dueDisplay = '';
        if ($dueTimestamp === null && $dueRaw !== '') {
            $parsed = strtotime($dueRaw);
            if ($parsed !== false) {
                $dueTimestamp = $parsed;
            }
        }
        if ($dueTimestamp !== null) {
            $dueDate = date('Y-m-d', $dueTimestamp);
            $dueDisplay = date('M j, Y', $dueTimestamp);
        } elseif ($dueRaw !== '') {
            $dueDisplay = $dueRaw;
        }

        $normalizedTasks[] = [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'completed' => $completed,
            'owner' => $owner,
            'due_date' => $dueDate,
            'due_display' => $dueDisplay,
            'due_timestamp' => $dueTimestamp,
            'priority' => $priority,
            'priority_label' => $priorityLabel,
            'notes' => $notes,
            'weight' => $weight,
        ];
    }
    $normalized['tasks'] = $normalizedTasks;
    $normalized['task_progress'] = fg_content_module_task_progress($normalizedTasks);

    $guideNormalizer = static function ($guides): array {
        if (is_array($guides)) {
            $normalizedGuides = [];
            foreach ($guides as $index => $guide) {
                if (is_string($guide)) {
                    $parts = explode('|', $guide, 2);
                    $title = trim($parts[0] ?? '');
                    if ($title === '') {
                        $title = 'Guide ' . ($index + 1);
                    }
                    $prompt = trim($parts[1] ?? '');
                    $normalizedGuides[] = [
                        'title' => $title,
                        'prompt' => $prompt,
                    ];
                } elseif (is_array($guide)) {
                    $title = trim((string) ($guide['title'] ?? $guide['label'] ?? ''));
                    if ($title === '') {
                        $title = 'Guide ' . ($index + 1);
                    }
                    $prompt = trim((string) ($guide['prompt'] ?? $guide['description'] ?? ''));
                    $normalizedGuides[] = [
                        'title' => $title,
                        'prompt' => $prompt,
                    ];
                }
            }

            return $normalizedGuides;
        }

        return [];
    };

    $guidesRaw = $module['guides'] ?? [];
    if (!is_array($guidesRaw)) {
        $guidesRaw = [];
    }

    $normalized['guides'] = [
        'micro' => $guideNormalizer($guidesRaw['micro'] ?? ($module['micro_guides'] ?? [])),
        'macro' => $guideNormalizer($guidesRaw['macro'] ?? ($module['macro_guides'] ?? [])),
    ];

    $relationshipSources = $module['relationships'] ?? ($module['related_modules'] ?? []);
    $normalized['relationships'] = fg_normalize_content_module_relationships($relationshipSources);

    if (empty($normalized['guides']['micro'])) {
        $normalized['guides']['micro'] = [
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

    if (empty($normalized['guides']['macro'])) {
        $normalized['guides']['macro'] = [
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

    return $normalized;
}
