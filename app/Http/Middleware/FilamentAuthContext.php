<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Filament\Facades\Filament;
use App\Models\User;

class FilamentAuthContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Intentar obtener el usuario de diferentes fuentes
        $userId = null;
        $username = null;
        
        // 1. Intentar obtener el usuario de Auth facade
        if (Auth::check()) {
            $user = Auth::user();
            $userId = $user->id;
            $username = $user->username;
            
            Log::debug('FilamentAuthContext: Usuario obtenido de Auth facade', [
                'user_id' => $userId,
                'username' => $username,
            ]);
        }
        // 2. Intentar obtener el usuario de Filament
        else if (Filament::auth()->check()) {
            $user = Filament::auth()->user();
            $userId = $user->id;
            $username = $user->username;
            
            Log::debug('FilamentAuthContext: Usuario obtenido de Filament', [
                'user_id' => $userId,
                'username' => $username,
            ]);
        }
        // 3. Intentar obtener el usuario de la sesión
        else if (session()->has('filament.auth.id')) {
            $userId = session('filament.auth.id');
            $user = User::find($userId);
            
            if ($user) {
                $username = $user->username;
                Log::debug('FilamentAuthContext: Usuario obtenido de la sesión de Filament', [
                    'user_id' => $userId,
                    'username' => $username,
                ]);
            }
        }
        
        // Si se encontró un usuario, guardarlo en la sesión
        if ($userId) {
            session(['auth_user_id' => $userId]);
            
            Log::info('FilamentAuthContext: Usuario guardado en sesión', [
                'user_id' => $userId,
                'username' => $username,
            ]);
        } else {
            Log::warning('FilamentAuthContext: No se pudo obtener el usuario autenticado');
        }
        
        return $next($request);
    }
}
