<?php

function fg_default_translations_dataset(): array
{
    return [
        'tokens' => [
            'nav.feed' => [
                'label' => 'Navigation · Feed',
                'description' => 'Primary link to the feed of recent activity.'
            ],
            'nav.profile' => [
                'label' => 'Navigation · Profile',
                'description' => "Link to the signed-in member's profile."
            ],
            'nav.settings' => [
                'label' => 'Navigation · Settings',
                'description' => 'Link to the personal and delegated settings page.'
            ],
            'nav.setup' => [
                'label' => 'Navigation · Setup',
                'description' => 'Administrative setup dashboard entry.'
            ],
            'nav.pages' => [
                'label' => 'Navigation · Pages',
                'description' => 'Link to the pages directory.'
            ],
            'nav.knowledge' => [
                'label' => 'Navigation · Knowledge base',
                'description' => 'Link to the knowledge base directory.'
            ],
            'nav.sign_out' => [
                'label' => 'Navigation · Sign out',
                'description' => 'Sign-out button label.'
            ],
            'nav.sign_in' => [
                'label' => 'Navigation · Sign in',
                'description' => 'Sign-in link label for guests.'
            ],
            'nav.register' => [
                'label' => 'Navigation · Register',
                'description' => 'Account creation link label for guests.'
            ],
            'feed.composer.heading' => [
                'label' => 'Feed · Composer heading',
                'description' => 'Heading above the composer form.'
            ],
            'feed.latest_activity.heading' => [
                'label' => 'Feed · Latest activity heading',
                'description' => 'Heading above the recent posts list.'
            ],
            'feed.knowledge.heading' => [
                'label' => 'Feed · Knowledge base heading',
                'description' => 'Heading above the knowledge base summary panel.'
            ],
            'register.heading' => [
                'label' => 'Register · Heading',
                'description' => 'Title shown on the registration page.'
            ],
            'register.submit' => [
                'label' => 'Register · Submit button',
                'description' => 'Label for the create profile button.'
            ],
            'settings.heading' => [
                'label' => 'Settings · Heading',
                'description' => 'Title shown on the settings page.'
            ]
        ],
        'locales' => [
            'en' => [
                'label' => 'English (US)',
                'strings' => [
                    'nav.feed' => 'Feed',
                    'nav.profile' => 'My Profile',
                    'nav.settings' => 'Settings',
                    'nav.setup' => 'Setup',
                    'nav.pages' => 'Pages',
                    'nav.knowledge' => 'Knowledge base',
                    'nav.sign_out' => 'Sign out',
                    'nav.sign_in' => 'Sign in',
                    'nav.register' => 'Create account',
                    'feed.composer.heading' => 'Share something',
                    'feed.latest_activity.heading' => 'Latest activity',
                    'feed.knowledge.heading' => 'Knowledge base',
                    'register.heading' => 'Create your profile',
                    'register.submit' => 'Create profile',
                    'settings.heading' => 'Settings'
                ]
            ]
        ],
        'fallback_locale' => 'en'
    ];
}

