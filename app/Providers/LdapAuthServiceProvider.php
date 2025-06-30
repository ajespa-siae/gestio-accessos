<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Attempting;
use LdapRecord\Container;

class LdapAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Escuchar el evento de intento de autenticación
        Event::listen(Attempting::class, function (Attempting $event) {
            // Verificar si estamos intentando autenticar con nombre de usuario y contraseña
            if (isset($event->credentials['username']) && isset($event->credentials['password'])) {
                $username = $event->credentials['username'];
                $password = $event->credentials['password'];
                
                Log::debug('Interceptando intento de autenticación LDAP', [
                    'username' => $username,
                    'ip' => request()->ip(),
                ]);
                
                // Definir los formatos de nombre de usuario a probar
                $formats = [];
                
                // Formato original
                $formats[] = $username;
                
                // Formato UPN (username@domain) si no tiene ya un @
                if (!str_contains($username, '@')) {
                    $formats[] = $username . '@esparreguera.local';
                }
                
                // Formato domain\username si no tiene ya un \
                if (!str_contains($username, '\\')) {
                    $formats[] = 'ESPARREGUERA\\' . $username;
                }
                
                // Intentar autenticación directa con LDAP para cada formato
                try {
                    $connection = Container::getDefaultConnection();
                    
                    foreach ($formats as $index => $formattedUsername) {
                        try {
                            Log::debug("Probando formato [$index]: $formattedUsername");
                            
                            if ($connection->auth()->attempt($formattedUsername, $password)) {
                                Log::info('Autenticación LDAP directa exitosa', [
                                    'formato' => $index,
                                    'username_original' => $username,
                                    'username_formateado' => $formattedUsername,
                                ]);
                                
                                // Si la autenticación directa funciona, modificar el nombre de usuario
                                // en las credenciales para que Laravel Auth lo use
                                $event->credentials['username'] = $formattedUsername;
                                break;
                            }
                        } catch (\Exception $e) {
                            Log::warning("Error en formato [$index]", [
                                'username' => $formattedUsername,
                                'error' => $e->getMessage(),
                            ]);
                            // Continuar con el siguiente formato
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error al conectar con el servidor LDAP', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
        
        // Registrar un evento cuando la autenticación falla
        Event::listen('auth.failed', function ($credentials, $remember) {
            Log::warning('Autenticación fallida', [
                'username' => $credentials['username'] ?? 'no proporcionado',
                'ip' => request()->ip(),
            ]);
        });
        
        // Registrar un evento cuando la autenticación es exitosa
        Event::listen('auth.login', function ($user, $remember) {
            Log::info('Autenticación exitosa', [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'rol' => $user->rol_principal,
                'ip' => request()->ip(),
            ]);
            
            // Si el usuario no tiene un DN de LDAP, intentar actualizarlo
            if (empty($user->ldap_dn)) {
                try {
                    $ldapUser = \App\Ldap\User::query()
                        ->where('samaccountname', '=', $user->username)
                        ->orWhere('mail', '=', $user->email)
                        ->first();
                    
                    if ($ldapUser) {
                        $user->ldap_dn = $ldapUser->getDn();
                        $user->ldap_last_sync = now();
                        $user->save();
                        
                        Log::info('DN de LDAP actualizado', [
                            'user_id' => $user->id,
                            'ldap_dn' => $user->ldap_dn,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error al actualizar DN de LDAP', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }
}
