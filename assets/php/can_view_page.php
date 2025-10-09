<?php

require_once __DIR__ . '/is_admin.php';

function fg_can_view_page(array $page, ?array $user): bool
{
    $visibility = $page['visibility'] ?? 'public';
    if ($visibility === 'public') {
        return true;
    }

    if ($visibility === 'members') {
        return $user !== null;
    }

    if ($visibility === 'roles') {
        if ($user === null) {
            return false;
        }
        $role = (string) ($user['role'] ?? 'member');
        $allowedRoles = array_map('strval', $page['allowed_roles'] ?? []);
        if (empty($allowedRoles)) {
            return fg_is_admin($user);
        }
        return in_array($role, $allowedRoles, true);
    }

    return fg_is_admin($user);
}

