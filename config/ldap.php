<?php

return [
    'default' => env('LDAP_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'hosts' => [env('LDAP_HOST', 'esparreguera.local')],
            'username' => env('LDAP_USERNAME'),
            'password' => env('LDAP_PASSWORD'),
            'port' => env('LDAP_PORT', 389),
            'base_dn' => env('LDAP_BASE_DN'),
            'timeout' => env('LDAP_TIMEOUT', 5),
            'use_ssl' => env('LDAP_SSL', false),
            'use_tls' => env('LDAP_TLS', false),
            'options' => [
                LDAP_OPT_REFERRALS => 0,
                LDAP_OPT_PROTOCOL_VERSION => 3,
            ],
        ],
    ],

    // Configuración de autenticación
    'authentication' => [
        // Permitir múltiples formatos de autenticación
        'format' => null, // Usar null para probar todos los formatos
        'domain' => 'esparreguera.local',
        'fallback_formats' => [
            'upn' => '@esparreguera.local',           // username@esparreguera.local
            'domain_slash' => 'ESPARREGUERA\\',      // ESPARREGUERA\username  
            'dn' => true,                            // DN complet
        ],
    ],
];