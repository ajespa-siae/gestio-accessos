<?php

namespace App\Auth;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use LdapRecord\Container;
use App\Models\User;

class LdapFilamentAuthenticator
{
    /**
     * Intenta autenticar un usuario con LDAP y sincronizarlo con la base de datos.
     *
     * @param string $username
     * @param string $password
     * @return User|null
     */
    public static function attempt(string $username, string $password): ?User
    {
        Log::debug('LdapFilamentAuthenticator: Iniciando intento de autenticación', [
            'username' => $username,
            'ip' => request()->ip(),
        ]);
        
        // Formatos de nombre de usuario a probar
        $formats = [
            // Formato original
            $username,
            
            // Formato UPN (username@domain) si no tiene ya un @
            !str_contains($username, '@') ? $username . '@esparreguera.local' : null,
            
            // Formato domain\username si no tiene ya un \
            !str_contains($username, '\\') ? 'ESPARREGUERA\\' . $username : null,
        ];
        
        // Filtrar formatos nulos
        $formats = array_filter($formats);
        
        // Intentar autenticación directa con LDAP para cada formato
        try {
            $connection = Container::getDefaultConnection();
            $authenticatedFormat = null;
            $ldapUser = null;
            
            foreach ($formats as $index => $formattedUsername) {
                try {
                    Log::debug("LdapFilamentAuthenticator: Probando formato", [
                        'index' => $index,
                        'username' => $formattedUsername
                    ]);
                    
                    if ($connection->auth()->attempt($formattedUsername, $password)) {
                        Log::info('LdapFilamentAuthenticator: Autenticación LDAP directa exitosa', [
                            'username' => $formattedUsername,
                        ]);
                        
                        $authenticatedFormat = $formattedUsername;
                        
                        // Extraer el nombre de usuario base (sin dominio)
                        $baseUsername = $username;
                        if (str_contains($username, '@')) {
                            $baseUsername = explode('@', $username)[0];
                        } elseif (str_contains($username, '\\')) {
                            $baseUsername = explode('\\', $username)[1];
                        }
                        
                        // Buscar el usuario en LDAP
                        $ldapUser = \App\Ldap\User::query()
                            ->where('samaccountname', '=', $baseUsername)
                            ->first();
                        
                        if ($ldapUser) {
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('LdapFilamentAuthenticator: Error en autenticación LDAP', [
                        'username' => $formattedUsername,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Si no se pudo autenticar con ningún formato, retornar null
            if (!$authenticatedFormat || !$ldapUser) {
                Log::warning('LdapFilamentAuthenticator: No se pudo autenticar con ningún formato');
                return null;
            }
            
            // Buscar o crear el usuario en la base de datos
            $databaseUser = User::where('username', $ldapUser->getFirstAttribute('samaccountname'))
                ->orWhere('email', $ldapUser->getFirstAttribute('mail'))
                ->first();
            
            if (!$databaseUser) {
                // Crear un nuevo usuario en la base de datos
                $syncArray = $ldapUser->toSyncArray();
                $syncArray['password'] = bcrypt(\Illuminate\Support\Str::random(16));
                
                $databaseUser = User::create($syncArray);
                
                Log::info('LdapFilamentAuthenticator: Usuario creado en la base de datos', [
                    'id' => $databaseUser->id,
                    'username' => $databaseUser->username,
                ]);
            } else {
                // Actualizar el usuario existente
                $syncArray = $ldapUser->toSyncArray();
                
                // Si el usuario no tiene contraseña, generar una
                if (empty($databaseUser->password)) {
                    $syncArray['password'] = bcrypt(\Illuminate\Support\Str::random(16));
                }
                
                $databaseUser->update($syncArray);
                
                Log::info('LdapFilamentAuthenticator: Usuario actualizado en la base de datos', [
                    'id' => $databaseUser->id,
                    'username' => $databaseUser->username,
                ]);
            }
            
            // Autenticar al usuario en Laravel
            Auth::login($databaseUser);
            
            return $databaseUser;
        } catch (\Exception $e) {
            Log::error('LdapFilamentAuthenticator: Error general', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return null;
        }
    }
}
