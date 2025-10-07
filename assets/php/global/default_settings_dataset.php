<?php

function fg_default_settings_dataset(): array
{
    return [
        'settings' => [
            'app_name' => [
                'label' => 'Application Name',
                'description' => 'Name displayed across the network.',
                'value' => 'Filegate',
                'managed_by' => 'admins',
                'allowed_roles' => ['admin'],
                'category' => 'branding',
            ],
            'default_theme' => [
                'label' => 'Default Theme',
                'description' => 'Palette applied to visitors who have not personalised their account.',
                'value' => 'filegate_midnight',
                'managed_by' => 'admins',
                'allowed_roles' => ['admin'],
                'category' => 'branding',
            ],
            'theme_personalisation_policy' => [
                'label' => 'Theme Personalisation Policy',
                'description' => 'Determines whether members can override palette tokens from the settings page.',
                'value' => 'enabled',
                'managed_by' => 'custom',
                'allowed_roles' => ['admin', 'moderator'],
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
            'notification_channel_defaults' => [
                'label' => 'Default Notification Channels',
                'description' => 'Channels queued when posts or settings trigger notifications.',
                'value' => ['email', 'browser', 'file-cache'],
                'managed_by' => 'admins',
                'allowed_roles' => ['admin'],
                'category' => 'notifications',
            ],
            'notification_cache_driver' => [
                'label' => 'Notification Cache Driver',
                'description' => 'Defines how notifications are cached for delivery.',
                'value' => 'file',
                'managed_by' => 'admins',
                'allowed_roles' => ['admin'],
                'category' => 'infrastructure',
            ],
            'cookie_notice_policy' => [
                'label' => 'Cookie Notice Policy',
                'description' => 'Controls whether the cookie banner is informational or required.',
                'value' => 'informational',
                'managed_by' => 'everyone',
                'allowed_roles' => [],
                'category' => 'privacy',
            ],
            'upload_limits' => [
                'label' => 'Upload Limits',
                'description' => 'Maximum number of attachments processed per post.',
                'value' => 5,
                'managed_by' => 'admins',
                'allowed_roles' => ['admin'],
                'category' => 'infrastructure',
            ],
            'template_selection_mode' => [
                'label' => 'Template Selection Mode',
                'description' => 'Determines whether members can choose from all templates or curated sets.',
                'value' => 'curated',
                'managed_by' => 'custom',
                'allowed_roles' => ['admin', 'moderator'],
                'category' => 'content',
            ],
        ],
        'role_definitions' => [
            'admin' => 'Full access to all datasets and settings delegation.',
            'moderator' => 'May assist with collaboration and template curation.',
            'member' => 'Standard participant with profile and posting abilities.',
        ],
    ];
}

