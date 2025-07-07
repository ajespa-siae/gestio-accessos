<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function __invoke(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // Usar el formato UPN por defecto (usuario@dominio)
        $username = $credentials['username'];
        if (!str_contains($username, '@') && !str_contains($username, '\\')) {
            $username .= '@esparreguera.local';
        }

        // Intentar autenticar al usuario
        if (Auth::attempt(['username' => $username, 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();
            
            // Verificar si el usuario está activo
            if (!$user->actiu) {
                Auth::logout();
                return back()->withErrors([
                    'username' => 'Aquest compte està desactivat. Si us plau, contacta amb l\'administrador.',
                ]);
            }
            
            // Redirigir según el rol del usuario
            if ($user->hasRole('admin')) {
                return redirect()->intended(route('filament.admin.pages.dashboard'));
            }
            
            // Si tiene algún rol operativo, redirigir al panel operativo
            if ($user->hasAnyRole(['rrhh', 'it', 'gestor'])) {
                return redirect()->intended(route('filament.operatiu.pages.dashboard'));
            }
            
            // Cerrar sesión si no tiene roles válidos
            Auth::logout();
            return back()->withErrors([
                'username' => 'No tens cap rol vàlid assignat. Si us plau, contacta amb l\'administrador.',
            ]);
        }

        // Si llegamos aquí, la autenticación falló
        return back()->withErrors([
            'username' => 'Les credencials proporcionades no són vàlides.',
        ]);
    }
}
