<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\FilamentAuthContext;

class MiddlewareServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar el middleware CheckRole
        Route::aliasMiddleware('check.role', CheckRole::class);
        
        // Registrar el middleware FilamentAuthContext
        Route::aliasMiddleware('filament.auth.context', FilamentAuthContext::class);
        
        // Aplicar el middleware FilamentAuthContext a todas las rutas de Filament
        // En Laravel 12, necesitamos usar el método prependMiddlewareToGroup
        app('router')->prependMiddlewareToGroup('web', FilamentAuthContext::class);
    }
}
