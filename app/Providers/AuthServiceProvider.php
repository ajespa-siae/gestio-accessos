<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Auth\LdapUpnUserProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \Spatie\Permission\Models\Role::class => \App\Policies\RolePolicy::class,
        \App\Models\ProcessMobilitat::class => \App\Policies\ProcessMobilitatPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // ✅ REGISTRAR PROVIDER UPN PERSONALITZAT
        Auth::provider('ldap_upn', function ($app, array $config) {
            return new LdapUpnUserProvider(
                $app['hash'],
                $config['model']
            );
        });
        
        // Si hi ha altres registres, afegir aquí
    }
}
