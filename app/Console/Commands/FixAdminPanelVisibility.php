<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class FixAdminPanelVisibility extends Command
{
    protected $signature = 'shield:fix-admin-visibility {--user=8 : ID del usuario super_admin}';
    protected $description = 'Corrige la visibilidad de recursos en el panel admin';

    public function handle()
    {
        $this->info('Iniciando corrección de visibilidad del panel admin...');
        
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
        
        // 2. Regenerar políticas y permisos
        $this->info('Regenerando políticas y permisos...');
        try {
            Artisan::call('shield:generate --all --panel=admin --no-interaction');
            $this->info('Políticas y permisos regenerados para panel admin.');
        } catch (\Exception $e) {
            $this->error('Error al regenerar políticas: ' . $e->getMessage());
        }
        
        // 3. Asignar super_admin
        $userId = $this->option('user');
        $this->info("Asignando rol super_admin al usuario ID: {$userId}...");
        
        try {
            $user = User::find($userId);
            if (!$user) {
                $this->error("Usuario con ID {$userId} no encontrado.");
                return Command::FAILURE;
            }
            
            // Asignar rol super_admin
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if (!$superAdminRole) {
                $this->info('Creando rol super_admin...');
                $superAdminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
            }
            
            // Asignar rol al usuario
            $user->assignRole($superAdminRole);
            $this->info("Rol super_admin asignado al usuario {$user->name} (ID: {$userId})");
            
            // 4. Asignar todos los permisos al rol super_admin
            $this->info('Asignando todos los permisos al rol super_admin...');
            $permissions = Permission::all();
            $superAdminRole->syncPermissions($permissions);
            $this->info('Se han asignado ' . $permissions->count() . ' permisos al rol super_admin.');
            
            // 5. Verificar asignación
            $this->info('Verificando asignación de roles y permisos...');
            $userRoles = $user->roles()->pluck('name')->toArray();
            $userPermissions = $user->getAllPermissions()->count();
            
            $this->info("Usuario {$user->name} tiene los roles: " . implode(', ', $userRoles));
            $this->info("Usuario {$user->name} tiene {$userPermissions} permisos.");
            
            // 6. Forzar visibilidad de recursos específicos
            $this->info('Forzando visibilidad de recursos específicos...');
            
            // Asegurar que existen los permisos básicos para ver recursos
            $this->ensureBasicPermissions();
            
            $this->info('Corrección de visibilidad completada.');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Error al asignar super_admin: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    protected function ensureBasicPermissions()
    {
        // Asegurar que existen permisos básicos para los recursos principales
        $resources = [
            'role', 'user', 'empleat', 'departament', 'solicitud', 'ticket'
        ];
        
        $actions = [
            'view_any', 'view', 'create', 'update', 'delete'
        ];
        
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if (!$superAdminRole) {
            $this->error('No se encontró el rol super_admin');
            return;
        }
        
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permName = "{$action}_{$resource}";
                
                // Verificar si el permiso existe
                $permission = Permission::where('name', $permName)->first();
                
                // Si no existe, crearlo
                if (!$permission) {
                    $this->info("Creando permiso: {$permName}");
                    $permission = Permission::create(['name' => $permName, 'guard_name' => 'web']);
                }
                
                // Asignar al rol super_admin
                if (!$superAdminRole->hasPermissionTo($permName)) {
                    $superAdminRole->givePermissionTo($permission);
                    $this->info("Permiso {$permName} asignado a super_admin");
                }
            }
        }
    }
}
