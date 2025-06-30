<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Http\Controllers\Auth\LoginController as FilamentLoginController;
use App\Filament\Auth\LoginController;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar nuestro controlador de login personalizado
        $this->app->bind(FilamentLoginController::class, LoginController::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
