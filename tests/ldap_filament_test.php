<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importar clases necesarias
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Ldap\User as LdapUser;
use LdapRecord\Container;

// Configurar el log para ver detalles
Log::info('Iniciando prueba de autenticación LDAP con Filament');

echo "=== DIAGNÓSTICO DE AUTENTICACIÓN LDAP PARA FILAMENT ===\n\n";

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

// Verificar si el proveedor personalizado está registrado
echo "Verificando proveedores de autenticación registrados:\n";
$providers = Auth::getProviders();
foreach ($providers as $name => $provider) {
    echo "- Proveedor: " . get_class($provider) . "\n";
}
echo "\n";

// Datos de prueba
$username = 'MCampmany';
$password = 'Inffor@2021';

// Probar autenticación directa con LDAP
echo "Probando autenticación directa con LDAP:\n";
try {
    $connection = Container::getDefaultConnection();
    
    // Probar diferentes formatos
    $formats = [
        'original' => $username,
        'upn' => $username . '@esparreguera.local',
        'domain_slash' => 'ESPARREGUERA\\' . $username
    ];
    
    foreach ($formats as $format => $formattedUsername) {
        echo "- Formato $format: $formattedUsername... ";
        try {
            if ($connection->auth()->attempt($formattedUsername, $password)) {
                echo "✅ ÉXITO\n";
            } else {
                echo "❌ FALLO\n";
            }
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error al conectar con LDAP: " . $e->getMessage() . "\n";
}
echo "\n";

// Probar autenticación con Laravel Auth
echo "Probando autenticación con Laravel Auth:\n";
foreach ($formats as $format => $formattedUsername) {
    echo "- Formato $format: $formattedUsername... ";
    
    try {
        if (Auth::attempt(['username' => $formattedUsername, 'password' => $password])) {
            echo "✅ ÉXITO\n";
            $user = Auth::user();
            echo "  Usuario: " . $user->name . "\n";
            echo "  Email: " . $user->email . "\n";
            echo "  Rol: " . $user->rol_principal . "\n";
            Auth::logout();
        } else {
            echo "❌ FALLO\n";
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Verificar si el usuario existe en la base de datos
echo "Verificando usuario en la base de datos:\n";
$dbUser = \App\Models\User::where('username', $username)->first();
if ($dbUser) {
    echo "✅ Usuario encontrado en la base de datos\n";
    echo "ID: " . $dbUser->id . "\n";
    echo "Nombre: " . $dbUser->name . "\n";
    echo "Email: " . $dbUser->email . "\n";
    echo "Rol: " . $dbUser->rol_principal . "\n";
    echo "LDAP DN: " . ($dbUser->ldap_dn ?: 'No disponible') . "\n";
    echo "Activo: " . ($dbUser->actiu ? 'Sí' : 'No') . "\n";
} else {
    echo "❌ Usuario no encontrado en la base de datos\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
