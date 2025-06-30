<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect('login');
        }
        
        $userRole = auth()->user()->rol_principal;
        
        if (!in_array($userRole, $roles)) {
            abort(403, 'No tens permisos per accedir a aquesta secció');
        }
        
        return $next($request);
    }
}
