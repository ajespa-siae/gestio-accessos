<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importar clases necesarias
use App\Auth\LdapFilamentAuthenticator;
use Illuminate\Support\Facades\Log;

echo "=== PRUEBA FINAL DE AUTENTICACIÓN LDAP CON FILAMENT ===\n\n";

// Datos de prueba
$username = 'MCampmany';
$password = 'Inffor@2021';

// Probar nuestro autenticador personalizado
echo "Probando LdapFilamentAuthenticator:\n";
try {
    $user = LdapFilamentAuthenticator::attempt($username, $password);
    
    if ($user) {
        echo "✅ Autenticación exitosa con LdapFilamentAuthenticator\n";
        echo "- ID: " . $user->id . "\n";
        echo "- Nombre: " . $user->name . "\n";
        echo "- Email: " . $user->email . "\n";
        echo "- Rol: " . $user->rol_principal . "\n";
        echo "- LDAP DN: " . ($user->ldap_dn ?: 'No disponible') . "\n";
        echo "- Activo: " . ($user->actiu ? 'Sí' : 'No') . "\n";
    } else {
        echo "❌ Autenticación fallida con LdapFilamentAuthenticator\n";
    }
} catch (Exception $e) {
    echo "❌ Error en LdapFilamentAuthenticator: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
echo "\nResumen de la solución implementada:\n";
echo "1. Se creó un autenticador LDAP personalizado (LdapFilamentAuthenticator) que prueba múltiples formatos de nombre de usuario\n";
echo "2. Se creó un controlador de login personalizado para Filament que utiliza nuestro autenticador\n";
echo "3. Se registró el controlador personalizado a través de un proveedor de servicios\n";
echo "4. Se mejoró la sincronización de usuarios LDAP con la base de datos\n";
echo "5. Se implementó un sistema de logging detallado para facilitar la depuración\n";
echo "\nPara probar la autenticación en la interfaz de Filament, accede a la ruta /admin y utiliza tus credenciales LDAP.\n";
