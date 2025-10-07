<?php

function fg_default_themes_dataset(): array
{
    return [
        'records' => [
            'filegate_midnight' => [
                'label' => 'Filegate Midnight',
                'description' => 'Balanced dark mode tuned for the default Filegate experience.',
                'tokens' => [
                    'surface' => '#0f172a',
                    'surface_alt' => '#15233b',
                    'text_primary' => '#f8fafc',
                    'text_secondary' => '#cbd5f5',
                    'accent' => '#38bdf8',
                    'accent_on' => '#041833',
                    'border' => '#273550',
                    'positive' => '#22c55e',
                    'warning' => '#f97316',
                    'negative' => '#ef4444',
                ],
            ],
            'dawn_horizon' => [
                'label' => 'Dawn Horizon',
                'description' => 'Warm sunrise palette ideal for lighter deployments.',
                'tokens' => [
                    'surface' => '#fff7ed',
                    'surface_alt' => '#fde68a',
                    'text_primary' => '#1f2937',
                    'text_secondary' => '#4b5563',
                    'accent' => '#f97316',
                    'accent_on' => '#fff7ed',
                    'border' => '#fbbf24',
                    'positive' => '#16a34a',
                    'warning' => '#f59e0b',
                    'negative' => '#dc2626',
                ],
            ],
        ],
        'metadata' => [
            'default' => 'filegate_midnight',
        ],
    ];
}

