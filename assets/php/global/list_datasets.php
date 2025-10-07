<?php

function fg_list_datasets(): array
{
    return [
        'users' => ['label' => 'User Profiles', 'description' => 'Profile-centric records including roles, privacy, and biography variables.'],
        'posts' => ['label' => 'Posts', 'description' => 'HTML5-ready post entries with metadata for privacy, collaborators, and conversation styles.'],
        'settings' => ['label' => 'Settings', 'description' => 'Delegated configuration entries defining application behavior.'],
    ];
}

