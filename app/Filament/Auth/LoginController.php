<?php

namespace App\Filament\Auth;

use App\Auth\LdapFilamentAuthenticator;
use Filament\Http\Controllers\Auth\LoginController as FilamentLoginController;
use Filament\Http\Responses\Auth\LoginResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginController extends FilamentLoginController
{
    /**
     * Sobrescribe el método de autenticación para usar nuestro autenticador LDAP personalizado
     */
    public function authenticate(Request $request): LoginResponse
    {
        Log::debug('Filament LoginController: Iniciando autenticación', [
            'username' => $request->input('username'),
            'ip' => $request->ip(),
        ]);
        
        // Validar la solicitud
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);
        
        // Intentar autenticar con nuestro autenticador LDAP personalizado
        $user = LdapFilamentAuthenticator::attempt(
            $request->input('username'),
            $request->input('password')
        );
        
        // Si la autenticación falló, redirigir con error
        if (!$user) {
            Log::warning('Filament LoginController: Autenticación fallida', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
            ]);
            
            return $this->failure($request);
        }
        
        // Registrar el éxito de la autenticación
        Log::info('Filament LoginController: Autenticación exitosa', [
            'user_id' => $user->id,
            'username' => $user->username,
            'ip' => $request->ip(),
        ]);
        
        // Retornar la respuesta de éxito
        return app(LoginResponse::class);
    }
}
