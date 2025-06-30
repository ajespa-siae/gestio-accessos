<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importar clases necesarias
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use LdapRecord\Container;

echo "=== DIAGNÓSTICO SIMPLE DE AUTENTICACIÓN LDAP ===\n\n";

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
