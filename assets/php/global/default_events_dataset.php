<?php

function fg_default_events_dataset(): array
{
    $now = date(DATE_ATOM);
    $upcoming = strtotime('+7 days');
    if ($upcoming === false) {
        $upcoming = time();
    }
    $upcomingEnd = $upcoming + 7200;

    return [
        'next_id' => 2,
        'records' => [
            [
                'id' => 1,
                'title' => 'Welcome to Filegate',
                'summary' => 'Tour the platform, explore datasets, and learn how to tailor every asset.',
                'description' => 'Join the core team for a walkthrough of Filegate\'s configurable assets, datasets, and workflows. We\'ll cover setup delegation, localized overrides, and how to operate entirely from shared hosting environments.',
                'status' => 'scheduled',
                'visibility' => 'members',
                'start_at' => date(DATE_ATOM, $upcoming),
                'end_at' => date(DATE_ATOM, $upcomingEnd),
                'timezone' => 'UTC',
                'location' => 'Community Hub (Virtual)',
                'location_url' => '',
                'allow_rsvp' => true,
                'rsvp_policy' => 'members',
                'rsvp_limit' => 50,
                'rsvps' => [],
                'hosts' => [1],
                'collaborators' => [],
                'tags' => ['onboarding', 'tour'],
                'attachments' => [],
                'created_at' => $now,
                'updated_at' => $now,
                'last_activity_at' => $now,
                'created_by' => 1,
                'updated_by' => 1,
            ],
        ],
    ];
}
