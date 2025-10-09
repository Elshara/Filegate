<?php

function fg_default_feature_requests_dataset(): array
{
    $now = date(DATE_ATOM);

    return [
        'next_id' => 3,
        'records' => [
            [
                'id' => 1,
                'title' => 'Modular profile sections',
                'summary' => 'Let members design profile layouts with reusable blocks and layout presets.',
                'details' => 'Design a drag-and-drop editor that keeps assets local while letting members arrange galleries, stats, and embeds per profile.',
                'status' => 'planned',
                'priority' => 'high',
                'visibility' => 'members',
                'requestor_user_id' => 1,
                'owner_role' => 'admin',
                'owner_user_id' => null,
                'tags' => ['profiles', 'editor'],
                'reference_links' => ['/setup.php#themes'],
                'supporters' => [1, 2],
                'vote_count' => 2,
                'impact' => 5,
                'effort' => 3,
                'admin_notes' => 'Prototype layout presets that reuse existing theme tokens.',
                'created_at' => $now,
                'updated_at' => $now,
                'last_activity_at' => $now,
            ],
            [
                'id' => 2,
                'title' => 'Dataset diff previews',
                'summary' => 'Preview dataset changes before saving snapshots from the setup dashboard.',
                'details' => 'Compare JSON payloads locally so administrators can review what changed before committing updates or restoring snapshots.',
                'status' => 'researching',
                'priority' => 'medium',
                'visibility' => 'members',
                'requestor_user_id' => 2,
                'owner_role' => 'moderator',
                'owner_user_id' => null,
                'tags' => ['datasets', 'admin'],
                'reference_links' => ['/setup.php#datasets'],
                'supporters' => [1],
                'vote_count' => 1,
                'impact' => 4,
                'effort' => 2,
                'admin_notes' => '',
                'created_at' => $now,
                'updated_at' => $now,
                'last_activity_at' => $now,
            ],
        ],
    ];
}

