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
use App\Models\User;

echo "=== DIAGNÓSTICO DE AUTENTICACIÓN FILAMENT CON LDAP ===\n\n";

// Datos de prueba
$username = 'MCampmany';
$password = 'Inffor@2021';

// Verificar la configuración de Filament
echo "Verificando configuración de Filament:\n";
$filamentAuthGuard = config('filament.auth.guard');
$filamentAuthProvider = config('auth.guards.' . $filamentAuthGuard . '.provider');
echo "- Guard de Filament: " . $filamentAuthGuard . "\n";
echo "- Provider del guard: " . $filamentAuthProvider . "\n";
echo "- Driver del provider: " . config('auth.providers.' . $filamentAuthProvider . '.driver') . "\n";
echo "- Modelo del provider: " . config('auth.providers.' . $filamentAuthProvider . '.model') . "\n\n";

// Verificar si el usuario existe en la base de datos
echo "Verificando usuario en la base de datos:\n";
$dbUser = User::where('username', $username)->first();
if ($dbUser) {
    echo "✅ Usuario encontrado en la base de datos\n";
    echo "- ID: " . $dbUser->id . "\n";
    echo "- Nombre: " . $dbUser->name . "\n";
    echo "- Email: " . $dbUser->email . "\n";
    echo "- Rol: " . $dbUser->rol_principal . "\n";
    echo "- LDAP DN: " . ($dbUser->ldap_dn ?: 'No disponible') . "\n";
    echo "- Contraseña: " . (empty($dbUser->password) ? 'No establecida' : 'Establecida') . "\n";
    echo "- Activo: " . ($dbUser->actiu ? 'Sí' : 'No') . "\n\n";
    
    // Si no tiene contraseña, establecer una temporal para pruebas
    if (empty($dbUser->password)) {
        $dbUser->password = bcrypt('temporal123');
        $dbUser->save();
        echo "✅ Se ha establecido una contraseña temporal para pruebas\n\n";
    }
} else {
    echo "❌ Usuario no encontrado en la base de datos\n\n";
}

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
                
                // Buscar el usuario LDAP
                $ldapUser = \App\Ldap\User::query()
                    ->where('samaccountname', '=', $username)
                    ->first();
                
                if ($ldapUser) {
                    echo "  DN: " . $ldapUser->getDn() . "\n";
                    echo "  Grupos: " . implode(', ', $ldapUser->getGroups()) . "\n";
                    
                    // Actualizar el DN en la base de datos si es necesario
                    if ($dbUser && empty($dbUser->ldap_dn)) {
                        $dbUser->ldap_dn = $ldapUser->getDn();
                        $dbUser->ldap_last_sync = now();
                        $dbUser->save();
                        echo "  ✅ DN actualizado en la base de datos\n";
                    }
                }
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

// Simular una solicitud de autenticación de Filament
echo "Simulando solicitud de autenticación de Filament:\n";
try {
    // Crear una solicitud simulada
    $request = new \Illuminate\Http\Request();
    $request->merge([
        'username' => $username,
        'password' => $password,
    ]);
    
    // Disparar el evento de intento de autenticación manualmente
    event(new \Illuminate\Auth\Events\Attempting(
        'web',
        ['username' => $username, 'password' => $password],
        false
    ));
    
    // Intentar autenticación
    if (Auth::attempt(['username' => $username, 'password' => $password])) {
        echo "✅ Autenticación simulada exitosa\n";
        $user = Auth::user();
        echo "- Usuario: " . $user->name . "\n";
        echo "- Email: " . $user->email . "\n";
        echo "- Rol: " . $user->rol_principal . "\n";
        Auth::logout();
    } else {
        echo "❌ Autenticación simulada fallida\n";
        
        // Intentar con los otros formatos
        foreach (['upn', 'domain_slash'] as $formatKey) {
            $formattedUsername = $formats[$formatKey];
            echo "  Probando con formato $formatKey: $formattedUsername... ";
            
            if (Auth::attempt(['username' => $formattedUsername, 'password' => $password])) {
                echo "✅ ÉXITO\n";
                $user = Auth::user();
                echo "  Usuario: " . $user->name . "\n";
                Auth::logout();
                break;
            } else {
                echo "❌ FALLO\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Error en la simulación: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
