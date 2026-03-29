<?php

declare(strict_types=1);

return [
    'audit' => [
        'driver' => null,
    ],

    'super_admin' => [
        'enabled' => false,
        'role_name' => null,
    ],

    'cache' => [
        'permission_map' => [
            'enabled' => false,
        ],
    ],

    'roles' => [
        'apply_default_role_hints' => false,
    ],
];
