<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Filament\Facades\Filament;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        $user = null;
        
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
        // 4. Intentar obtener el usuario de la sesión auth_user_id
        else if (session()->has('auth_user_id')) {
            $userId = session('auth_user_id');
            $user = User::find($userId);
            
            if ($user) {
                $username = $user->username;
                Log::debug('FilamentAuthContext: Usuario obtenido de la sesión auth_user_id', [
                    'user_id' => $userId,
                    'username' => $username,
                ]);
            }
        }
        
        // Si se encontró un usuario, guardarlo en la sesión y configurar Auth
        if ($userId) {
            session(['auth_user_id' => $userId]);
            
            // Si el usuario no está autenticado en Auth, autenticarlo
            if (!Auth::check()) {
                Auth::login($user);
                Log::info('FilamentAuthContext: Usuario autenticado en Auth', [
                    'user_id' => $userId,
                    'username' => $username,
                ]);
            }
            
            // Establecer el usuario actual para el contexto de la base de datos
            // Esto es útil para modelos que requieren el ID del usuario autenticado
            DB::statement("SET LOCAL gestor_rrhh.current_user_id = ?;", [$userId]);
            
            Log::info('FilamentAuthContext: Usuario guardado en sesión y contexto DB', [
                'user_id' => $userId,
                'username' => $username,
            ]);
        } else {
            Log::warning('FilamentAuthContext: No se pudo obtener el usuario autenticado');
        }
        
        return $next($request);
    }
}
