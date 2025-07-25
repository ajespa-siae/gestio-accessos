<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Spatie\Permission\Models\Permission;

class MobilitatShieldServiceProvider extends ServiceProvider
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
        // Personalitzar Shield per incloure permisos de mobilitat
        $this->extendShieldFormComponents();
    }
    
    private function extendShieldFormComponents(): void
    {
        // Extendre els components de formulari de Shield per incloure permisos de mobilitat
        // Això es farà directament al RoleResource personalitzat
    }
}
