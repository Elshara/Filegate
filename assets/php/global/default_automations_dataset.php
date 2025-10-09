<?php

function fg_default_automations_dataset(): array
{
    $now = date(DATE_ATOM);

    return [
        'next_id' => 2,
        'records' => [
            [
                'id' => 1,
                'name' => 'Welcome new members',
                'description' => 'Queue a welcome notification and log the signup event.',
                'status' => 'enabled',
                'trigger' => 'user_registered',
                'conditions' => [
                    [
                        'type' => 'role_equals',
                        'options' => [
                            'role' => 'member',
                        ],
                    ],
                ],
                'actions' => [
                    [
                        'type' => 'enqueue_notification',
                        'options' => [
                            'channel' => 'email',
                            'template' => 'post_update',
                        ],
                    ],
                    [
                        'type' => 'record_activity',
                        'options' => [
                            'message' => 'New member onboarding automation executed.',
                        ],
                    ],
                ],
                'owner_role' => 'admin',
                'owner_user_id' => null,
                'run_limit' => null,
                'run_count' => 0,
                'priority' => 'medium',
                'tags' => ['onboarding'],
                'created_at' => $now,
                'updated_at' => $now,
                'last_run_at' => null,
            ],
        ],
    ];
}

