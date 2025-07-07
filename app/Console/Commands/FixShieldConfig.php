<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class FixShieldConfig extends Command
{
    protected $signature = 'shield:fix-config';
    protected $description = 'Corrige la configuración de Shield para asegurar que define_via_gate está activado';

    public function handle()
    {
        $this->info('Corrigiendo configuración de Shield...');
        
        // 1. Limpiar cachés
        $this->info('Limpiando cachés...');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('optimize:clear');
        
        // 2. Verificar configuración actual
        $this->info('Configuración actual de Shield:');
        $this->line(' - super_admin.enabled: ' . (config('filament-shield.super_admin.enabled') ? 'true' : 'false'));
        $this->line(' - super_admin.name: ' . config('filament-shield.super_admin.name'));
        $this->line(' - super_admin.define_via_gate: ' . (config('filament-shield.super_admin.define_via_gate') ? 'true' : 'false'));
        $this->line(' - super_admin.intercept_gate: ' . config('filament-shield.super_admin.intercept_gate'));
        
        // 3. Verificar si el archivo de configuración existe
        $configPath = config_path('filament-shield.php');
        if (!File::exists($configPath)) {
            $this->error('El archivo de configuración no existe en: ' . $configPath);
            return Command::FAILURE;
        }
        
        // 4. Leer el contenido actual
        $content = File::get($configPath);
        $this->info('Contenido actual del archivo de configuración:');
        $this->line($content);
        
        // 5. Buscar y reemplazar la configuración de define_via_gate
        $pattern = "/'define_via_gate'\s*=>\s*(false|true),/";
        if (preg_match($pattern, $content)) {
            $newContent = preg_replace($pattern, "'define_via_gate' => true,", $content);
            
            // 6. Guardar el archivo modificado
            File::put($configPath, $newContent);
            $this->info('Configuración actualizada correctamente.');
            
            // 7. Verificar la nueva configuración
            Artisan::call('config:clear');
            $this->info('Nueva configuración de Shield:');
            $this->line(' - super_admin.define_via_gate: ' . (config('filament-shield.super_admin.define_via_gate') ? 'true' : 'false'));
        } else {
            $this->warn('No se encontró la configuración de define_via_gate en el archivo.');
            
            // Alternativa: Crear un archivo de configuración completo
            $this->info('Creando un nuevo archivo de configuración...');
            
            $newConfig = <<<EOT
<?php

return [
    'shield_resource' => [
        'should_register_navigation' => true,
        'slug' => 'shield/roles',
        'navigation_sort' => -1,
        'navigation_badge' => true,
        'navigation_group' => true,
        'sub_navigation_position' => null,
        'is_globally_searchable' => false,
        'show_model_path' => true,
        'is_scoped_to_tenant' => false,
        'cluster' => null,
    ],

    'tenant_model' => null,

    'auth_provider_model' => [
        'fqcn' => 'App\\Models\\User',
    ],

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => true,
        'intercept_gate' => 'before',
    ],

    'panel_user' => [
        'enabled' => true,
        'name' => 'panel_user',
    ],

    'permission_prefixes' => [
        'resource' => [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
        ],
        'page' => [
            'view',
        ],
        'widget' => [
            'view',
        ],
        'custom' => [],
    ],

    'entities' => [
        'pages' => true,
        'widgets' => true,
        'resources' => true,
        'custom_permissions' => true,
    ],

    'generator' => [
        'option' => 'policies_and_permissions',
    ],

    'exclude' => [
        'enabled' => true,
        'pages' => [
            'Dashboard',
        ],
        'widgets' => [
            'AccountWidget',
            'FilamentInfoWidget',
        ],
        'resources' => [],
    ],

    'register_role_policy' => [
        'enabled' => true,
    ],
];
EOT;
            
            File::put($configPath, $newConfig);
            $this->info('Nuevo archivo de configuración creado correctamente.');
            
            // Limpiar cachés nuevamente
            Artisan::call('config:clear');
            $this->info('Nueva configuración de Shield:');
            $this->line(' - super_admin.define_via_gate: ' . (config('filament-shield.super_admin.define_via_gate') ? 'true' : 'false'));
        }
        
        $this->info('Proceso completado. Recuerde reiniciar el servidor para aplicar los cambios.');
        
        return Command::SUCCESS;
    }
}
