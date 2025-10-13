<?php

require_once __DIR__ . '/load_content_modules.php';
require_once __DIR__ . '/normalize_content_module.php';

function fg_list_content_modules(?string $dataset = null, array $options = []): array
{
    $modules = fg_load_content_modules();
    $records = $modules['records'] ?? [];
    if (!is_array($records)) {
        return [];
    }

    $datasetFilter = $dataset !== null ? strtolower(trim($dataset)) : null;

    $statuses = $options['statuses'] ?? ['active'];
    if ($statuses === null) {
        $statuses = [];
    }
    if (is_string($statuses)) {
        $statuses = [$statuses];
    }
    if (!is_array($statuses)) {
        $statuses = [];
    }
    $statuses = array_values(array_unique(array_filter(array_map(static function ($status) {
        return strtolower(trim((string) $status));
    }, $statuses), static function ($status) {
        return $status !== '';
    })));

    $viewer = $options['viewer'] ?? null;
    $viewerRole = strtolower(trim((string) ($viewer['role'] ?? '')));
    $enforceVisibility = !empty($options['enforce_visibility']);

    $result = [];
    foreach ($records as $module) {
        if (!is_array($module)) {
            continue;
        }
        $normalized = fg_normalize_content_module_definition($module);
        if ($datasetFilter !== null && strtolower($normalized['dataset']) !== $datasetFilter) {
            continue;
        }
        if (!empty($statuses) && !in_array(strtolower($normalized['status'] ?? 'active'), $statuses, true)) {
            continue;
        }
        if ($enforceVisibility) {
            $visibility = strtolower($normalized['visibility'] ?? 'members');
            if ($visibility === 'admins' && $viewerRole !== 'admin') {
                continue;
            }
            if ($visibility === 'members' && empty($viewer)) {
                continue;
            }
            $allowedRoles = $normalized['allowed_roles'] ?? [];
            if (!is_array($allowedRoles)) {
                $allowedRoles = [];
            }
            $allowedRoles = array_values(array_filter(array_map(static function ($role) {
                return strtolower(trim((string) $role));
            }, $allowedRoles), static function ($role) {
                return $role !== '';
            }));
            if (!empty($allowedRoles) && ($viewerRole === '' || !in_array($viewerRole, $allowedRoles, true))) {
                continue;
            }
        }
        $result[$normalized['key']] = $normalized;
    }

    ksort($result);

    return $result;
}
