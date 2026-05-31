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
        'route' => 'change.dashboard',
        'icon'  => 'heroicon-o-arrows-right-left',
        'order' => 50,
    ],
];
