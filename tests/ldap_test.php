<?php

require_once __DIR__ . '/../vendor/autoload.php';

use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\Auth\BindException;

// Cargar variables de entorno
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configuración de conexión LDAP
$config = [
    'hosts' => [env('LDAP_HOST', 'esparreguera.local')],
    'base_dn' => env('LDAP_BASE_DN'),
    'username' => env('LDAP_USERNAME'),
    'password' => env('LDAP_PASSWORD'),
    'port' => env('LDAP_PORT', 389),
    'use_ssl' => (bool) env('LDAP_SSL', false),
    'use_tls' => (bool) env('LDAP_TLS', false),
    'timeout' => env('LDAP_TIMEOUT', 5),
    'options' => [
        LDAP_OPT_PROTOCOL_VERSION => 3,
        LDAP_OPT_REFERRALS => 0,
    ],
];

echo "Configuración LDAP:\n";
echo "Host: " . $config['hosts'][0] . "\n";
echo "Base DN: " . $config['base_dn'] . "\n";
echo "Username: " . $config['username'] . "\n";
echo "Port: " . $config['port'] . "\n";
echo "SSL: " . ($config['use_ssl'] ? 'Yes' : 'No') . "\n";
echo "TLS: " . ($config['use_tls'] ? 'Yes' : 'No') . "\n";

// Crear conexión
$connection = new Connection($config);

try {
    // Intentar conectar
    echo "\nIntentando conectar al servidor LDAP...\n";
    $connection->connect();
    echo "✅ Conexión exitosa al servidor LDAP\n";
    
    // Probar autenticación con bind
    echo "\nProbando autenticación con bind...\n";
    
    // Formato UPN (username@domain)
    $username = 'MCampmany@esparreguera.local';
    $password = 'Inffor@2021';
    
    echo "Intentando autenticar como: $username\n";
    
    try {
        $connection->auth()->attempt($username, $password);
        echo "✅ Autenticación exitosa con formato UPN\n";
    } catch (BindException $e) {
        echo "❌ Error de autenticación con formato UPN: " . $e->getMessage() . "\n";
        
        // Probar con formato domain\username
        $username = 'ESPARREGUERA\\MCampmany';
        echo "\nIntentando autenticar como: $username\n";
        
        try {
            $connection->auth()->attempt($username, $password);
            echo "✅ Autenticación exitosa con formato domain\\username\n";
        } catch (BindException $e) {
            echo "❌ Error de autenticación con formato domain\\username: " . $e->getMessage() . "\n";
            
            // Probar solo con username
            $username = 'MCampmany';
            echo "\nIntentando autenticar como: $username\n";
            
            try {
                $connection->auth()->attempt($username, $password);
                echo "✅ Autenticación exitosa con solo username\n";
            } catch (BindException $e) {
                echo "❌ Error de autenticación con solo username: " . $e->getMessage() . "\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}

// Probar la búsqueda de usuario
echo "\nBuscando usuario en LDAP...\n";
try {
    $connection->connect();
    
    // Intentar buscar el usuario
    $baseDn = $config['base_dn'];
    $filter = "(samaccountname=MCampmany)";
    $search = $connection->search()->setDn($baseDn)->rawFilter($filter);
    $results = $search->get();
    
    if (count($results) > 0) {
        echo "✅ Usuario encontrado en LDAP\n";
        echo "DN: " . $results[0]['dn'][0] . "\n";
        
        // Mostrar grupos
        if (isset($results[0]['memberof'])) {
            echo "\nGrupos del usuario:\n";
            foreach ($results[0]['memberof'] as $group) {
                echo "- $group\n";
                
                // Extraer CN del grupo
                if (preg_match('/CN=([^,]+)/i', $group, $matches)) {
                    echo "  (Nombre del grupo: " . $matches[1] . ")\n";
                }
            }
        } else {
            echo "El usuario no pertenece a ningún grupo\n";
        }
    } else {
        echo "❌ Usuario no encontrado en LDAP\n";
    }
} catch (\Exception $e) {
    echo "❌ Error en la búsqueda: " . $e->getMessage() . "\n";
}
