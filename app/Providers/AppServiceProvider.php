<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar manualmente las rutas de autenticación de Filament
        // Esto es necesario porque el diagnóstico muestra que Filament no se carga correctamente
        $this->registerFilamentRoutes();
    }
    
    /**
     * Registra manualmente las rutas de autenticación de Filament
     */
    protected function registerFilamentRoutes(): void
    {
        // Verificar si estamos en producción
        if (app()->environment('production')) {
            // Ruta POST para login
            if (!Route::has('filament.admin.auth.login')) {
                Route::post('/admin/login', function () {
                    // Intentar cargar el controlador de login de Filament
                    $controllerClass = '\Filament\Http\Controllers\Auth\LoginController';
                    
                    if (class_exists($controllerClass)) {
                        return app($controllerClass)->login();
                    }
                    
                    // Si no existe el controlador, redirigir a la página de login
                    return redirect('/admin/login');
                })->name('filament.admin.auth.login');
            }
        }
    }
}
