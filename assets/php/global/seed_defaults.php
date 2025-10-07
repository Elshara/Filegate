<?php

require_once __DIR__ . '/load_users.php';
require_once __DIR__ . '/save_users.php';
require_once __DIR__ . '/load_posts.php';
require_once __DIR__ . '/save_posts.php';
require_once __DIR__ . '/load_settings.php';
require_once __DIR__ . '/save_settings.php';

function fg_seed_defaults(): void
{
    $users = fg_load_users();
    if (!isset($users['records']) || !is_array($users['records'])) {
        $users = ['records' => [], 'next_id' => 1];
        fg_save_users($users);
    }

    $posts = fg_load_posts();
    if (!isset($posts['records']) || !is_array($posts['records'])) {
        $posts = ['records' => [], 'next_id' => 1];
        fg_save_posts($posts);
    }

    $settings = fg_load_settings();
    if (!isset($settings['settings']) || !is_array($settings['settings'])) {
        $settings = [
            'settings' => [
                'app_name' => [
                    'label' => 'Application Name',
                    'description' => 'Name displayed across the network.',
                    'value' => 'Filegate',
                    'managed_by' => 'admins',
                    'allowed_roles' => ['admin'],
                    'category' => 'branding',
                ],
                'profile_privacy' => [
                    'label' => 'Default Profile Privacy',
                    'description' => 'Determines whether new profiles are public or private.',
                    'value' => 'public',
                    'managed_by' => 'admins',
                    'allowed_roles' => ['admin'],
                    'category' => 'privacy',
                ],
                'post_custom_types' => [
                    'label' => 'Custom Post Type Availability',
                    'description' => 'Controls whether members can flag posts as custom types.',
                    'value' => 'enabled',
                    'managed_by' => 'everyone',
                    'allowed_roles' => [],
                    'category' => 'content',
                ],
                'rich_embed_policy' => [
                    'label' => 'Rich Embed Policy',
                    'description' => 'Determines whether detected URLs render as local embeds.',
                    'value' => 'enabled',
                    'managed_by' => 'admins',
                    'allowed_roles' => ['admin'],
                    'category' => 'content',
                ],
                'statistics_visibility' => [
                    'label' => 'Post Statistics Visibility',
                    'description' => 'Controls who can see calculated statistics such as word counts.',
                    'value' => 'public',
                    'managed_by' => 'custom',
                    'allowed_roles' => ['admin', 'moderator'],
                    'category' => 'content',
                ],
                'collaboration_mode' => [
                    'label' => 'Collaboration Controls',
                    'description' => 'Specifies if collaborators may edit shared posts.',
                    'value' => 'owner-only',
                    'managed_by' => 'custom',
                    'allowed_roles' => ['admin', 'moderator'],
                    'category' => 'collaboration',
                ],
            ],
            'role_definitions' => [
                'admin' => 'Full access to all datasets and settings delegation.',
                'moderator' => 'May assist with collaboration flows as delegated.',
                'member' => 'Standard participant with profile and posting abilities.',
            ],
        ];
        fg_save_settings($settings);
    }
}

