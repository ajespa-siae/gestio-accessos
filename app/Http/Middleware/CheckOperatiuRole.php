<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckOperatiuRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::channel('daily')->info('CheckOperatiuRole: Iniciando middleware para ruta: ' . $request->path());
        Log::channel('daily')->info('CheckOperatiuRole: Parámetros de la solicitud: ' . json_encode($request->all()));
        
        $user = auth()->user();
        Log::channel('daily')->info('CheckOperatiuRole: Usuario autenticado: ' . ($user ? $user->email : 'No autenticado'));
        
        // Si no hay usuario autenticado, redirigir al login
        if (!$user) {
            Log::channel('daily')->warning('CheckOperatiuRole: No hay usuario autenticado');
            if ($request->is('operatiu') || $request->is('operatiu/*')) {
                Log::channel('daily')->info('CheckOperatiuRole: Redirigiendo a login del panel operativo');
                return redirect()->route('filament.operatiu.auth.login');
            }
            Log::channel('daily')->info('CheckOperatiuRole: Redirigiendo a login general');
            return redirect()->route('login');
        }
        
        $operativeRoles = ['rrhh', 'it', 'gestor'];
        $isAdmin = $user->hasRole('admin');
        Log::channel('daily')->info('CheckOperatiuRole: ¿Es admin? ' . ($isAdmin ? 'Sí' : 'No'));
        
        // Verificar roles operativos manualmente para evitar problemas con hasAnyRole
        $userRoles = [];
        $hasOperativeRole = false;
        
        foreach ($operativeRoles as $role) {
            if ($user->hasRole($role)) {
                $userRoles[] = $role;
                $hasOperativeRole = true; // Si tiene al menos un rol operativo
            }
        }
        
        Log::channel('daily')->info('CheckOperatiuRole: Roles operativos del usuario: ' . implode(', ', $userRoles));
        Log::channel('daily')->info('CheckOperatiuRole: ¿Tiene rol operativo? ' . ($hasOperativeRole ? 'Sí' : 'No'));
        
        $currentPath = $request->path();
        Log::channel('daily')->info('CheckOperatiuRole: Ruta actual: ' . $currentPath);
        
        // Si está intentando acceder al panel operativo
        if (str_starts_with($currentPath, 'operatiu')) {
            Log::channel('daily')->info('CheckOperatiuRole: Intentando acceder al panel operativo');
            
            // Si viene desde el panel admin con el parámetro especial, permitir acceso siempre
            if ($request->has('from') && $request->input('from') === 'admin') {
                Log::channel('daily')->info('CheckOperatiuRole: Acceso desde panel admin con parámetro especial');
                // Establecer rol operativo para el contexto del panel
                $operativeRole = $this->getOperativeRole($user);
                session(['operatiu_role' => $operativeRole]);
                Log::channel('daily')->info('CheckOperatiuRole: Sesión establecida con operatiu_role: ' . $operativeRole);
                return $next($request);
            }
            
            // Si viene con el parámetro direct=1 (desde nuestro controlador puente)
            if ($request->has('direct') && $request->input('direct') === '1') {
                Log::channel('daily')->info('CheckOperatiuRole: Acceso directo desde controlador puente');
                return $next($request);
            }
            
            // Si tiene rol operativo, permitir acceso independientemente de si también es admin
            if ($hasOperativeRole) {
                Log::channel('daily')->info('CheckOperatiuRole: Usuario tiene rol operativo, permitiendo acceso');
                // Establecer rol operativo para el contexto del panel
                $operativeRole = $this->getOperativeRole($user);
                session(['operatiu_role' => $operativeRole]);
                Log::channel('daily')->info('CheckOperatiuRole: Sesión establecida con operatiu_role: ' . $operativeRole);
                return $next($request);
            } else {
                // Si es admin pero no tiene rol operativo, redirigir al panel de admin
                if ($isAdmin) {
                    Log::channel('daily')->warning('CheckOperatiuRole: Usuario es admin pero no tiene rol operativo, redirigiendo a admin');
                    return redirect('/admin')->with('info', 'Redirigit al panell d\'administració');
                }
                // Si no tiene permisos, mostrar error 403
                Log::channel('daily')->warning('CheckOperatiuRole: Usuario sin permisos, mostrando error 403');
                abort(403, 'No tens accés al portal operatiu. Contacta amb l\'administrador.');
            }
        }
        
        // Si está intentando acceder al panel de admin
        if (str_starts_with($currentPath, 'admin')) {
            // Si no es admin, redirigir según corresponda
            if (!$isAdmin) {
                // Si tiene roles operativos, redirigir al panel operativo
                if ($hasOperativeRole) {
                    return redirect('/operatiu')->with('info', 'Redirigit al portal operatiu');
                }
                // Si no tiene permisos, mostrar error 403
                abort(403, 'No tens accés al panell d\'administració.');
            }
            
            return $next($request);
        }
        
        // Para otras rutas, permitir el acceso
        return $next($request);
    }
    
    /**
     * Obtiene el rol operativo del usuario
     */
    private function getOperativeRole($user): string
    {
        // Prioridad: rol principal si es operativo, sino primer rol operativo encontrado
        $operativeRoles = ['rrhh', 'it', 'gestor'];
        
        if (in_array($user->rol_principal, $operativeRoles)) {
            return $user->rol_principal;
        }
        
        // Si tiene múltiples roles, devolver el primero operativo
        foreach ($operativeRoles as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }
        
        // Por defecto, usar el rol principal
        return $user->rol_principal;
    }
}
