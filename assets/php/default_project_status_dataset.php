<?php

function fg_default_project_status_dataset(): array
{
    return [
        'records' => [
            [
                'id' => 1,
                'title' => 'Authentication and profile management',
                'summary' => 'Accounts, permissions, and profile customization tools available in Filegate today.',
                'status' => 'built',
                'category' => 'Foundation',
                'owner_role' => 'admin',
                'owner_user_id' => null,
                'milestone' => 'Initial release',
                'progress' => 100,
                'links' => [],
                'created_at' => '2024-01-01T00:00:00+00:00',
                'updated_at' => '2024-01-01T00:00:00+00:00',
            ],
            [
                'id' => 2,
                'title' => 'Dataset operations and configuration dashboards',
                'summary' => 'Self-service tooling for administrators to manage assets, datasets, and overrides.',
                'status' => 'in_progress',
                'category' => 'Operations',
                'owner_role' => 'admin',
                'owner_user_id' => null,
                'milestone' => 'Configuration expansion',
                'progress' => 70,
                'links' => [],
                'created_at' => '2024-01-01T00:00:00+00:00',
                'updated_at' => '2024-01-01T00:00:00+00:00',
            ],
            [
                'id' => 3,
                'title' => 'Extended collaboration spaces',
                'summary' => 'Spaces for coordinating collaborators and audiences around specialised post types.',
                'status' => 'planned',
                'category' => 'Community',
                'owner_role' => 'moderator',
                'owner_user_id' => null,
                'milestone' => 'Upcoming roadmap',
                'progress' => 10,
                'links' => [],
                'created_at' => '2024-01-01T00:00:00+00:00',
                'updated_at' => '2024-01-01T00:00:00+00:00',
            ],
        ],
        'next_id' => 4,
    ];
}

