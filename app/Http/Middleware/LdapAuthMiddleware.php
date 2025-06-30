<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LdapRecord\Container;

class LdapAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo interceptar solicitudes de login
        if ($request->is('admin/login') && $request->isMethod('post')) {
            $username = $request->input('username');
            $password = $request->input('password');
            
            if ($username && $password) {
                Log::debug('Interceptando intento de login LDAP', ['username' => $username]);
                
                // Probar autenticación directa con diferentes formatos
                $formats = [
                    // Formato UPN (username@domain)
                    'upn' => !str_contains($username, '@') ? $username . '@esparreguera.local' : $username,
                    
                    // Formato domain\username
                    'domain_slash' => !str_contains($username, '\\') ? 'ESPARREGUERA\\' . $username : $username,
                    
                    // Formato original
                    'original' => $username,
                ];
                
                try {
                    $connection = Container::getDefaultConnection();
                    
                    foreach ($formats as $format => $formattedUsername) {
                        try {
                            if ($connection->auth()->attempt($formattedUsername, $password)) {
                                Log::info('Autenticación LDAP exitosa', [
                                    'formato' => $format, 
                                    'username' => $formattedUsername
                                ]);
                                
                                // Reemplazar el nombre de usuario en la solicitud
                                $request->merge(['username' => $formattedUsername]);
                                break;
                            }
                        } catch (\Exception $e) {
                            Log::warning('Error en autenticación LDAP', [
                                'formato' => $format,
                                'username' => $formattedUsername,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error al conectar con LDAP', ['error' => $e->getMessage()]);
                }
            }
        }
        
        return $next($request);
    }
}
