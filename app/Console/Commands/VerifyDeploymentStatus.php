<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use App\Models\User;

class VerifyDeploymentStatus extends Command
{
    protected $signature = 'deploy:verify-status';
    protected $description = 'Verifica el estado de los componentes críticos tras el despliegue';

    public function handle()
    {
        $this->info('=== RESUMEN DE ESTADO DEL DESPLIEGUE ===');
        $this->newLine();
        
        // Array para almacenar el estado de cada verificación
        $checks = [];
        
        // 1. Verificar registro de AuthServiceProvider
        $this->info('1. Verificando registro de AuthServiceProvider...');
        $authProviderRegistered = $this->isAuthProviderRegistered();
        $checks['auth_provider'] = $authProviderRegistered;
        $this->outputStatus('AuthServiceProvider registrado', $authProviderRegistered);
        
        // 2. Verificar configuración de Shield
        $this->info('2. Verificando configuración de Shield...');
        $shieldConfig = $this->checkShieldConfig();
        $checks['shield_config'] = $shieldConfig['all_ok'];
        $this->outputStatus('define_via_gate = true', $shieldConfig['define_via_gate']);
        $this->outputStatus('is_scoped_to_tenant = false', $shieldConfig['is_scoped_to_tenant']);
        
        // 3. Verificar políticas registradas
        $this->info('3. Verificando políticas registradas...');
        $policies = $this->checkPolicies();
        $checks['policies'] = $policies['all_ok'];
        $this->outputStatus('Política de User registrada', $policies['user_policy']);
        $this->outputStatus('Política de Role registrada', $policies['role_policy']);
        
        // 4. Verificar permisos del super_admin
        $this->info('4. Verificando permisos del super_admin...');
        $permissions = $this->checkSuperAdminPermissions();
        $checks['permissions'] = $permissions['has_permissions'];
        $this->outputStatus('Usuario con rol super_admin', $permissions['has_role']);
        $this->outputStatus('Permisos críticos asignados', $permissions['has_permissions']);
        
        // Resumen final
        $this->newLine();
        $this->info('=== RESUMEN FINAL ===');
        $allOk = !in_array(false, $checks);
        
        if ($allOk) {
            $this->info('✅ DESPLIEGUE EXITOSO: Todos los componentes críticos están correctamente configurados.');
        } else {
            $this->error('❌ ADVERTENCIA: Algunos componentes críticos no están correctamente configurados.');
            
            // Mostrar qué componentes fallaron
            if (!$checks['auth_provider']) {
                $this->error('- AuthServiceProvider no está registrado correctamente.');
            }
            if (!$checks['shield_config']) {
                $this->error('- La configuración de Shield no es correcta.');
            }
            if (!$checks['policies']) {
                $this->error('- Las políticas no están registradas correctamente.');
            }
            if (!$checks['permissions']) {
                $this->error('- Los permisos del super_admin no están configurados correctamente.');
            }
            
            $this->warn('Ejecute los siguientes comandos para intentar corregir los problemas:');
            $this->line('php artisan config:clear');
            $this->line('php artisan cache:clear');
            $this->line('php artisan optimize:clear');
            $this->line('php artisan shield:fix-config');
            $this->line('php artisan shield:super-admin --user=8');
            $this->line('php artisan shield:assign-all-permissions');
        }
        
        return $allOk ? Command::SUCCESS : Command::FAILURE;
    }
    
    private function outputStatus($message, $status)
    {
        if ($status) {
            $this->line("  <fg=green>✓</> $message");
        } else {
            $this->line("  <fg=red>✗</> $message");
        }
    }
    
    private function isAuthProviderRegistered()
    {
        // Verificar si AuthServiceProvider está en el array de providers
        $providers = config('app.providers', []);
        return in_array('App\\Providers\\AuthServiceProvider', $providers);
    }
    
    private function checkShieldConfig()
    {
        $defineViaGate = config('filament-shield.super_admin.define_via_gate', false);
        $isScopedToTenant = config('filament-shield.shield_resource.is_scoped_to_tenant', true);
        
        return [
            'define_via_gate' => $defineViaGate === true,
            'is_scoped_to_tenant' => $isScopedToTenant === false,
            'all_ok' => $defineViaGate === true && $isScopedToTenant === false
        ];
    }
    
    private function checkPolicies()
    {
        // Verificar si las políticas están registradas en Gate
        $userPolicy = Gate::getPolicyFor(User::class);
        $rolePolicy = Gate::getPolicyFor(Role::class);
        
        return [
            'user_policy' => $userPolicy !== null,
            'role_policy' => $rolePolicy !== null,
            'all_ok' => $userPolicy !== null && $rolePolicy !== null
        ];
    }
    
    private function checkSuperAdminPermissions()
    {
        // Buscar un usuario con rol super_admin
        $superAdmin = User::role('super_admin')->first();
        
        if (!$superAdmin) {
            return [
                'has_role' => false,
                'has_permissions' => false
            ];
        }
        
        // Verificar permisos críticos
        $criticalPermissions = [
            'view_role',
            'view_any_role',
            'create_role',
            'update_role',
            'delete_role',
            'view_user',
            'view_any_user',
            'create_user',
            'update_user',
            'delete_user'
        ];
        
        $hasAllPermissions = true;
        foreach ($criticalPermissions as $permission) {
            if (!$superAdmin->hasPermissionTo($permission)) {
                $hasAllPermissions = false;
                break;
            }
        }
        
        return [
            'has_role' => true,
            'has_permissions' => $hasAllPermissions
        ];
    }
}
