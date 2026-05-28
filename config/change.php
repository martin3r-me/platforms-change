<?php

return [
    'name' => 'Change',
    'description' => 'Change Management Module (Kotter 8-Step Model)',
    'version' => '1.0.0',

    'scope_type' => 'parent',

    'routing' => [
        'prefix' => 'change',
        'middleware' => ['web', 'auth'],
    ],

    'guard' => 'web',

    'navigation' => [
        'main' => [
            'change' => [
                'title' => 'Change',
                'icon' => 'heroicon-o-arrows-right-left',
                'route' => 'change.dashboard',
            ],
        ],
    ],

    'sidebar' => [
        'change' => [
            'title' => 'Change',
            'icon' => 'heroicon-o-arrows-right-left',
            'items' => [
                'projects' => [
                    'title' => 'Change-Projekte',
                    'route' => 'change.projects.index',
                    'icon' => 'heroicon-o-rectangle-stack',
                ],
            ],
        ],
    ],
];
