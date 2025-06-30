<?php

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'ldap', // ✅ TORNAR A PROVIDER LDAP ORIGINAL
        ],

        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        // Proveedor LDAP estándar
        'ldap' => [
            'driver' => 'ldap', // Usar el driver estándar
            'model' => App\Ldap\User::class,
            'rules' => [],
            'scopes' => [],
            'database' => [
                'model' => App\Models\User::class,
                'sync_passwords' => false,
                'sync_attributes' => [
                    'name' => 'cn',
                    'email' => 'mail',
                    'username' => 'samaccountname',
                    'nif' => 'employeeid',
                ],
                'sync_existing' => [
                    'username' => 'samaccountname',
                ],
            ],
        ],

        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
