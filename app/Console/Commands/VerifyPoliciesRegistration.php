<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;
use ReflectionClass;

class VerifyPoliciesRegistration extends Command
{
    protected $signature = 'shield:verify-policies';
    protected $description = 'Verifica el registro y carga de políticas en tiempo de ejecución';

    public function handle()
    {
        $this->info('Verificando registro de políticas en tiempo de ejecución...');
        
        // Obtener todas las políticas registradas
        $policies = app('Illuminate\Contracts\Auth\Access\Gate')->policies();
        
        $this->info('Políticas registradas en Gate:');
        if (empty($policies)) {
            $this->warn('No se encontraron políticas registradas');
        } else {
            foreach ($policies as $model => $policy) {
                $this->line(" - {$model} => " . (is_string($policy) ? $policy : (is_object($policy) ? get_class($policy) : 'Tipo desconocido')));
            }
        }
        
        // Verificar políticas específicas
        $this->info('Verificando políticas específicas:');
        $modelsToCheck = [
            'App\Models\User',
            'Spatie\Permission\Models\Role',
        ];
        
        foreach ($modelsToCheck as $model) {
            $policy = Gate::getPolicyFor($model);
            if ($policy) {
                $this->info(" - {$model} => " . (is_string($policy) ? $policy : get_class($policy)));
            } else {
                $this->error(" - {$model} => No tiene política registrada");
            }
        }
        
        // Verificar si las políticas están en los directorios correctos
        $this->info('Verificando archivos de políticas:');
        $policyFiles = [
            'UserPolicy' => app_path('Policies/UserPolicy.php'),
            'RolePolicy' => app_path('Policies/RolePolicy.php'),
        ];
        
        foreach ($policyFiles as $name => $path) {
            if (file_exists($path)) {
                $this->info(" - {$name}: Existe en {$path}");
                // Mostrar contenido resumido
                $content = file_get_contents($path);
                $this->line("   Primeras líneas: " . substr($content, 0, 150) . "...");
            } else {
                $this->error(" - {$name}: No existe en {$path}");
            }
        }
        
        // Verificar si las políticas están siendo cargadas por el autoloader
        $this->info('Verificando carga de clases:');
        $policyClasses = [
            'App\Policies\UserPolicy',
            'App\Policies\RolePolicy',
        ];
        
        foreach ($policyClasses as $class) {
            if (class_exists($class)) {
                $this->info(" - {$class}: Clase cargable");
            } else {
                $this->error(" - {$class}: Clase NO cargable");
            }
        }
        
        // Verificar si el AuthServiceProvider está siendo cargado
        $this->info('Verificando AuthServiceProvider:');
        $authServiceProvider = 'App\Providers\AuthServiceProvider';
        if (class_exists($authServiceProvider)) {
            $this->info(" - {$authServiceProvider}: Clase cargable");
            
            // Verificar si el AuthServiceProvider está registrado
            $providers = app()->getLoadedProviders();
            if (isset($providers[$authServiceProvider])) {
                $this->info(" - {$authServiceProvider}: Proveedor registrado");
            } else {
                $this->error(" - {$authServiceProvider}: Proveedor NO registrado");
            }
        } else {
            $this->error(" - {$authServiceProvider}: Clase NO cargable");
        }
        
        return Command::SUCCESS;
    }
}
