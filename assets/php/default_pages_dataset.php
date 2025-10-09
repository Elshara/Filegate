<?php

function fg_default_pages_dataset(): array
{
    return [
        'records' => [
            [
                'id' => 1,
                'slug' => 'welcome',
                'title' => 'Welcome to Filegate',
                'summary' => 'Introduce your community guidelines, collaboration notes, or deployment details here.',
                'content' => '<p>Customize this welcome page from the setup dashboard to explain how Filegate operates for your community. Every element can be adjusted without editing code.</p>',
                'visibility' => 'public',
                'allowed_roles' => [],
                'show_in_navigation' => true,
                'created_at' => date(DATE_ATOM),
                'updated_at' => date(DATE_ATOM),
                'template' => 'standard',
                'format' => 'html',
                'variables' => [],
                'owner_id' => null,
            ],
        ],
        'next_id' => 2,
    ];
}

