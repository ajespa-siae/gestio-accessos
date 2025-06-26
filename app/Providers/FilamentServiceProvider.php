<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class FilamentServiceProvider extends ServiceProvider
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
        // Aseguramos que las rutas de autenticación se registren correctamente
        $this->app->resolving('filament', function ($filament, $app) {
            // Registramos manualmente la ruta POST para login si es necesario
            if (!$app['router']->has('filament.admin.auth.login.post')) {
                $app['router']->post('/admin/login', function () {
                    return app(\Filament\Http\Controllers\Auth\LoginController::class)->login();
                })->name('filament.admin.auth.login.post');
            }
        });
    }
}
