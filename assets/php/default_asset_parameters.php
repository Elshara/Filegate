<?php

require_once __DIR__ . '/asset_scope.php';

function fg_default_asset_parameters(string $relative_path): array
{
    $scope = fg_asset_scope($relative_path);
    return [
        'enabled' => [
            'label' => 'Enable Asset',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Toggle whether this asset is active.',
            'allow_user_override' => false,
            'baseline_allow_user_override' => false,
        ],
        'mode' => [
            'label' => 'Mode',
            'type' => 'select',
            'options' => ['default', 'minimal', 'extended'],
            'default' => 'default',
            'description' => 'Choose the default operating mode for this asset.',
            'allow_user_override' => ($scope === 'local'),
            'baseline_allow_user_override' => ($scope === 'local'),
        ],
        'variant' => [
            'label' => 'Template Variant',
            'type' => 'text',
            'default' => '',
            'description' => 'Optional variant keyword applied when rendering.',
            'allow_user_override' => true,
            'baseline_allow_user_override' => true,
        ],
    ];
}
