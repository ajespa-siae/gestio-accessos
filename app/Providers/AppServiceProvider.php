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
        // Siempre registrar las rutas, independientemente del entorno
        // Registrar la ruta POST para login con el nombre correcto
        Route::post('/admin/login', function () {
            // Intentar procesar el login manualmente
            $request = request();
            $email = $request->input('email');
            $password = $request->input('password');
            $remember = $request->boolean('remember');
            
            // Intentar autenticar al usuario
            if (auth()->attempt(['email' => $email, 'password' => $password], $remember)) {
                // Regenerar la sesión
                $request->session()->regenerate();
                
                // Redirigir al dashboard
                return redirect('/admin');
            }
            
            // Si falla la autenticación, redirigir de vuelta con error
            return back()->withErrors([
                'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
            ]);
        });
    }
}
