<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

// Cargar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Configurar el log para ver detalles
Log::info('Iniciando prueba de autenticación LDAP');

// Función para probar diferentes formatos de autenticación
function testAuth($username, $password) {
    echo "Probando autenticación con username: $username\n";
    
    // Activar el log detallado
    config(['app.debug' => true]);
    
    try {
        // Intentar autenticación
        $result = Auth::attempt([
            'username' => $username,
            'password' => $password
        ]);
        
        if ($result) {
            echo "✅ Autenticación exitosa\n";
            $user = Auth::user();
            echo "Usuario autenticado: " . $user->name . "\n";
            echo "Rol: " . $user->rol_principal . "\n";
            return true;
        } else {
            echo "❌ Autenticación fallida\n";
            return false;
        }
    } catch (\Exception $e) {
        echo "❌ Error en autenticación: " . $e->getMessage() . "\n";
        return false;
    }
}

// Mostrar configuración actual
echo "Configuración LDAP actual:\n";
echo "Conexión por defecto: " . config('ldap.default') . "\n";
echo "Host: " . config('ldap.connections.default.hosts.0') . "\n";
echo "Base DN: " . config('ldap.connections.default.base_dn') . "\n";
echo "Formato de autenticación: " . (config('ldap.authentication.format') ?: 'null (probar todos)') . "\n";
echo "Dominio: " . config('ldap.authentication.domain') . "\n\n";

// Mostrar configuración de Auth
echo "Configuración Auth actual:\n";
echo "Guard por defecto: " . config('auth.defaults.guard') . "\n";
echo "Provider del guard web: " . config('auth.guards.web.provider') . "\n";
echo "Driver del provider LDAP: " . config('auth.providers.ldap.driver') . "\n";
echo "Modelo LDAP: " . config('auth.providers.ldap.model') . "\n\n";

// Probar diferentes formatos de autenticación
$password = 'Inffor@2021';

// 1. Probar con formato completo UPN
$username1 = 'MCampmany@esparreguera.local';
echo "1. Formato UPN completo:\n";
testAuth($username1, $password);
echo "\n";

// 2. Probar solo con nombre de usuario
$username2 = 'MCampmany';
echo "2. Solo nombre de usuario:\n";
testAuth($username2, $password);
echo "\n";

// 3. Probar con formato domain\username
$username3 = 'ESPARREGUERA\\MCampmany';
echo "3. Formato domain\\username:\n";
testAuth($username3, $password);
echo "\n";

// Verificar si el usuario existe en la base de datos
echo "Verificando si el usuario existe en la base de datos:\n";
try {
    $dbUser = \App\Models\User::where('username', 'MCampmany')->first();
    if ($dbUser) {
        echo "✅ Usuario encontrado en la base de datos\n";
        echo "ID: " . $dbUser->id . "\n";
        echo "Nombre: " . $dbUser->name . "\n";
        echo "Email: " . $dbUser->email . "\n";
        echo "Rol: " . $dbUser->rol_principal . "\n";
        echo "LDAP DN: " . $dbUser->ldap_dn . "\n";
    } else {
        echo "❌ Usuario no encontrado en la base de datos\n";
    }
} catch (\Exception $e) {
    echo "❌ Error al buscar usuario en la base de datos: " . $e->getMessage() . "\n";
}
