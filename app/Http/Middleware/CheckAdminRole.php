<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Si no hay usuario autenticado, permitir el acceso a la página de login
        if (!$user) {
            return $next($request);
        }
        
        $isAdmin = $user->hasRole('admin');
        $hasOperativeRole = $user->hasAnyRole(['rrhh', 'it', 'gestor']);
        
        // Si está intentando acceder al panel de admin pero no es admin
        if (str_starts_with($request->path(), 'admin') && !$isAdmin) {
            // Si tiene roles operativos, redirigir al panel operativo
            if ($hasOperativeRole) {
                return redirect('/operatiu')->with('info', 'Redirigit al portal operatiu');
            }
            // Si no tiene permisos, mostrar error 403
            abort(403, 'No tens accés al panell d\'administració.');
        }
        
        // Si está intentando acceder al panel operativo pero no tiene permisos
        if (str_starts_with($request->path(), 'operatiu') && !$hasOperativeRole) {
            // Si es admin, redirigir al panel de admin
            if ($isAdmin) {
                return redirect('/admin')->with('info', 'Redirigit al panell d\'administració');
            }
            // Si no tiene permisos, mostrar error 403
            abort(403, 'No tens accés al portal operatiu. Contacta amb l\'administrador.');
        }
        
        return $next($request);
    }
}
