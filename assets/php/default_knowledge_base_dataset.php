<?php

function fg_default_knowledge_base_dataset(): array
{
    return [
        'records' => [
            [
                'id' => 1,
                'slug' => 'getting-started',
                'title' => 'Getting started with Filegate',
                'summary' => 'Set up roles, datasets, and assets on a fresh install without editing code.',
                'content' => '<p>Use the setup dashboard to review every dataset, apply defaults, and delegate controls. Profiles manage their own uploads, templates, and preferences while admins curate global standards.</p>',
                'tags' => ['setup', 'profiles', 'datasets'],
                'visibility' => 'public',
                'status' => 'published',
                'template' => 'article',
                'author_user_id' => null,
                'attachments' => [],
                'created_at' => '2024-01-01T00:00:00+00:00',
                'updated_at' => '2024-01-01T00:00:00+00:00'
            ],
        ],
        'next_id' => 2,
    ];
}
