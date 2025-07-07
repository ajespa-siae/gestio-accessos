<?php

return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'ldap',
        ],

        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        // Proveedor LDAP estándar
        'ldap' => [
            'driver' => 'ldap',
            'model' => App\Ldap\User::class,
            'rules' => [],
            'scopes' => [],
            'database' => [
                'model' => App\Models\User::class,
                'sync_passwords' => [
                    'column' => 'password',
                    'sync' => false, // No sincronizar contraseñas ya que usamos LDAP
                ],
                'sync_attributes' => [
                    'name' => 'cn',
                    'email' => 'mail',
                    'username' => 'samaccountname',
                    'nif' => 'employeeid',
                    'actiu' => function () { return true; }, // Por defecto, los usuarios de LDAP están activos
                ],
                'sync_existing' => [
                    'username' => 'samaccountname',
                ],
                'password_column' => 'password',
                'timestamps' => [
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
            ],
            'resolver' => [
                'username_field' => 'username',
                'dn_field' => 'ldap_dn',
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
