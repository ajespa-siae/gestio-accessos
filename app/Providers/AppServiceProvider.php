<?php

namespace App\Providers;

use App\View\Components\UserMenu;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

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
        // Registrar el componente de menú de usuario personalizado
        Blade::component('user-menu', UserMenu::class);
        
        // Compartir la configuración del menú de usuario con todas las vistas de Filament
        FilamentView::registerRenderHook(
            'panels::global-search.before',
            fn (): View => view('filament.components.global-search')
        );
    }
}
