<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // Si el usuario ya está autenticado, redirigir según su rol
        if (auth()->check()) {
            $user = auth()->user();
            
            if ($user->hasRole('admin')) {
                return redirect('/admin');
            }
            
            if ($user->hasAnyRole(['rrhh', 'it', 'gestor'])) {
                return redirect('/operatiu');
            }
        }
        
        // Si no está autenticado o no tiene roles válidos, redirigir al login del panel operativo
        return redirect('/operatiu/login');
    }
}
