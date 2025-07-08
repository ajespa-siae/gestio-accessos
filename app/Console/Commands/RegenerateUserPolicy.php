<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class RegenerateUserPolicy extends Command
{
    protected $signature = 'shield:regenerate-user-policy';
    protected $description = 'Regenera específicamente la política de usuario para Shield';

    public function handle()
    {
        $this->info('Regenerando política de usuario para Shield...');
        
        // 1. Limpiar cachés
        $this->info('Limpiando cachés...');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        Artisan::call('optimize:clear');
        
        try {
            Artisan::call('permission:cache-reset');
            $this->info('Caché de permisos limpiada.');
        } catch (\Exception $e) {
            $this->warn('No se pudo limpiar la caché de permisos: ' . $e->getMessage());
        }
        
        // 2. Verificar si existe la política de usuario
        $userPolicyPath = app_path('Policies/UserPolicy.php');
        if (File::exists($userPolicyPath)) {
            $this->info('La política de usuario ya existe en: ' . $userPolicyPath);
            $this->info('Contenido actual:');
            $this->line(File::get($userPolicyPath));
        } else {
            $this->warn('No existe política de usuario en: ' . $userPolicyPath);
        }
        
        // 3. Regenerar políticas
        $this->info('Regenerando políticas...');
        try {
            Artisan::call('shield:generate', [
                '--resource' => 'UserResource',
                '--panel' => 'admin',
                '--no-interaction' => true
            ]);
            $this->info('Política de usuario regenerada.');
        } catch (\Exception $e) {
            $this->error('Error al regenerar política de usuario: ' . $e->getMessage());
        }
        
        // 4. Verificar si se creó la política
        if (File::exists($userPolicyPath)) {
            $this->info('La política de usuario se ha creado/actualizado en: ' . $userPolicyPath);
            $this->info('Nuevo contenido:');
            $this->line(File::get($userPolicyPath));
        } else {
            $this->error('No se pudo crear la política de usuario en: ' . $userPolicyPath);
        }
        
        // 5. Registrar la política manualmente
        $this->info('Verificando registro de políticas en AuthServiceProvider...');
        $authServiceProviderPath = app_path('Providers/AuthServiceProvider.php');
        
        if (File::exists($authServiceProviderPath)) {
            $content = File::get($authServiceProviderPath);
            $this->line('Contenido actual de AuthServiceProvider:');
            $this->line($content);
            
            // Verificar si ya está registrada la política
            if (strpos($content, 'App\\Models\\User::class') !== false) {
                $this->info('La política de usuario ya está registrada en AuthServiceProvider.');
            } else {
                $this->warn('La política de usuario no está registrada en AuthServiceProvider. Considere añadirla manualmente.');
                $this->line('Ejemplo de registro:');
                $this->line("protected \$policies = [");
                $this->line("    App\\Models\\User::class => App\\Policies\\UserPolicy::class,");
                $this->line("];");
            }
        } else {
            $this->error('No se encontró AuthServiceProvider en: ' . $authServiceProviderPath);
        }
        
        $this->info('Proceso completado. Recuerde limpiar cachés y reiniciar el servidor para aplicar los cambios.');
        
        return Command::SUCCESS;
    }
}
