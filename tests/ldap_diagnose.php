<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importar la clase User de LDAP
use App\Ldap\User;
use Illuminate\Support\Facades\Auth;

// Datos de prueba
$username = 'MCampmany';
$password = 'Inffor@2021';

echo "=== DIAGNÓSTICO DE AUTENTICACIÓN LDAP ===\n\n";

// Ejecutar diagnóstico
echo "Ejecutando diagnóstico con usuario: $username\n";
$result = User::diagnoseAuthentication($username, $password);

// Mostrar resultados
echo "\nResultados del diagnóstico:\n";
echo "Éxito: " . ($result['success'] ? 'Sí' : 'No') . "\n\n";

echo "Mensajes:\n";
foreach ($result['messages'] as $message) {
    echo "- $message\n";
}

// Intentar autenticación con Laravel Auth
echo "\n=== PRUEBA DE AUTENTICACIÓN CON LARAVEL AUTH ===\n\n";

// Probar con diferentes formatos
$formats = [
    'username' => $username,
    'upn' => $username . '@esparreguera.local',
    'domain_slash' => 'ESPARREGUERA\\' . $username
];

foreach ($formats as $format => $formattedUsername) {
    echo "Probando formato $format: $formattedUsername\n";
    
    $authResult = Auth::attempt([
        'username' => $formattedUsername,
        'password' => $password
    ]);
    
    echo "Resultado: " . ($authResult ? '✅ Éxito' : '❌ Fallo') . "\n\n";
}

// Verificar si el usuario existe en la base de datos
echo "=== VERIFICACIÓN DE USUARIO EN BASE DE DATOS ===\n\n";
$dbUser = \App\Models\User::where('username', $username)->first();

if ($dbUser) {
    echo "✅ Usuario encontrado en la base de datos\n";
    echo "ID: " . $dbUser->id . "\n";
    echo "Nombre: " . $dbUser->name . "\n";
    echo "Email: " . $dbUser->email . "\n";
    echo "Rol: " . $dbUser->rol_principal . "\n";
    echo "LDAP DN: " . ($dbUser->ldap_dn ?: 'No disponible') . "\n";
    echo "Activo: " . ($dbUser->actiu ? 'Sí' : 'No') . "\n";
    
    // Intentar actualizar el DN si está vacío
    if (empty($dbUser->ldap_dn) && isset($result['user'])) {
        echo "\nActualizando DN del usuario en la base de datos...\n";
        $dbUser->ldap_dn = $result['user']->getDn();
        $dbUser->ldap_last_sync = now();
        $dbUser->save();
        echo "DN actualizado a: " . $dbUser->ldap_dn . "\n";
    }
} else {
    echo "❌ Usuario no encontrado en la base de datos\n";
    
    // Si tenemos el usuario LDAP, intentar crear el registro en la base de datos
    if (isset($result['user'])) {
        echo "\nCreando usuario en la base de datos desde LDAP...\n";
        $ldapUser = $result['user'];
        $syncArray = $ldapUser->toSyncArray();
        
        $newUser = new \App\Models\User($syncArray);
        $newUser->password = bcrypt(str_random(16)); // Contraseña aleatoria
        $newUser->save();
        
        echo "✅ Usuario creado en la base de datos con ID: " . $newUser->id . "\n";
    }
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
