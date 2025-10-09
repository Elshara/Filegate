<?php

function fg_default_polls_dataset(): array
{
    $now = date(DATE_ATOM);

    return [
        'next_id' => 2,
        'records' => [
            [
                'id' => 1,
                'question' => 'Which local-first enhancement should ship next?',
                'description' => 'Prioritise the next improvement to Filegate\'s self-hosted workflows.',
                'status' => 'open',
                'visibility' => 'members',
                'allow_multiple' => false,
                'max_selections' => 1,
                'owner_role' => 'admin',
                'owner_user_id' => null,
                'options' => [
                    [
                        'id' => 1,
                        'label' => 'Diff previews for dataset snapshots',
                        'supporters' => [1, 2],
                        'vote_count' => 2,
                    ],
                    [
                        'id' => 2,
                        'label' => 'Automation rules for notification queues',
                        'supporters' => [2],
                        'vote_count' => 1,
                    ],
                    [
                        'id' => 3,
                        'label' => 'Profile layout preset library',
                        'supporters' => [],
                        'vote_count' => 0,
                    ],
                ],
                'total_votes' => 3,
                'total_responses' => 2,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
                'last_activity_at' => $now,
                'closed_at' => null,
                'expires_at' => null,
                'next_option_id' => 4,
            ],
        ],
    ];
}
