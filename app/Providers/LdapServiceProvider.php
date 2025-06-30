<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use LdapRecord\Container;

class LdapServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // No registrem res aquí per evitar conflictes
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Només configurar logging si LDAP està habilitat i l'app està bootejada
        if ($this->app->runningInConsole() || !config('ldap.logging', false)) {
            return;
        }

        try {
            // Verificar que el container LDAP existeix abans de configurar logging
            if (Container::hasConnection('default')) {
                $connection = Container::getDefaultConnection();
                
                if ($connection && $this->app->bound('log')) {
                    $connection->setLogger(
                        $this->app->make('log')->channel('single')
                    );
                }
            }
        } catch (\Exception $e) {
            // Silenciar errors durant el bootstrap per evitar trencar l'app
            report($e);
        }
    }
}