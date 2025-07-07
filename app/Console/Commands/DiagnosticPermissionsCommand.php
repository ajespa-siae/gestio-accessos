<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class DiagnosticPermissionsCommand extends Command
{
    protected $signature = 'shield:diagnostic-permissions {--user=8 : ID del usuario a diagnosticar}';
    protected $description = 'Diagnostica problemas de permisos y políticas para un usuario específico';

    public function handle()
    {
        $userId = $this->option('user');
        $this->info("Diagnosticando permisos para el usuario ID: {$userId}");
        
        // 1. Verificar si el usuario existe
        $user = User::find($userId);
        if (!$user) {
            $this->error("El usuario con ID {$userId} no existe");
            return Command::FAILURE;
        }
        
        $this->info("Usuario: {$user->name} (ID: {$userId})");
        
        // 2. Verificar roles asignados
        $this->info("Roles asignados directamente:");
        $roles = $user->roles()->pluck('name')->toArray();
        if (empty($roles)) {
            $this->warn("El usuario no tiene roles asignados directamente");
        } else {
            foreach ($roles as $role) {
                $this->line(" - {$role}");
            }
        }
        
        // 3. Verificar permisos asignados directamente
        $this->info("Permisos asignados directamente:");
        $directPermissions = $user->getDirectPermissions()->pluck('name')->toArray();
        if (empty($directPermissions)) {
            $this->warn("El usuario no tiene permisos asignados directamente");
        } else {
            foreach ($directPermissions as $permission) {
                $this->line(" - {$permission}");
            }
        }
        
        // 4. Verificar permisos heredados de roles
        $this->info("Permisos heredados de roles:");
        $permissionsViaRoles = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        if (empty($permissionsViaRoles)) {
            $this->warn("El usuario no tiene permisos heredados de roles");
        } else {
            foreach ($permissionsViaRoles as $permission) {
                $this->line(" - {$permission}");
            }
        }
        
        // 5. Verificar todos los permisos efectivos
        $this->info("Todos los permisos efectivos:");
        $allPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        if (empty($allPermissions)) {
            $this->error("El usuario no tiene permisos efectivos");
        } else {
            foreach ($allPermissions as $permission) {
                $this->line(" - {$permission}");
            }
        }
        
        // 6. Verificar si el usuario tiene el rol super_admin
        $this->info("Verificación de rol super_admin:");
        if ($user->hasRole('super_admin')) {
            $this->info("El usuario tiene el rol super_admin");
        } else {
            $this->error("El usuario NO tiene el rol super_admin");
        }
        
        // 7. Verificar si el usuario tiene el rol admin
        $this->info("Verificación de rol admin:");
        if ($user->hasRole('admin')) {
            $this->info("El usuario tiene el rol admin");
        } else {
            $this->error("El usuario NO tiene el rol admin");
        }
        
        // 8. Verificar permisos específicos para recursos clave
        $this->info("Verificación de permisos específicos para recursos clave:");
        $resourcePermissions = [
            'view_any_role',
            'view_role',
            'create_role',
            'update_role',
            'delete_role',
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
        ];
        
        foreach ($resourcePermissions as $permission) {
            if ($user->can($permission)) {
                $this->info(" - {$permission}: SÍ tiene permiso");
            } else {
                $this->error(" - {$permission}: NO tiene permiso");
            }
        }
        
        // 9. Verificar configuración de Shield
        $this->info("Configuración de Shield:");
        $this->line(" - super_admin.enabled: " . (config('filament-shield.super_admin.enabled') ? 'true' : 'false'));
        $this->line(" - super_admin.name: " . config('filament-shield.super_admin.name'));
        $this->line(" - super_admin.define_via_gate: " . (config('filament-shield.super_admin.define_via_gate') ? 'true' : 'false'));
        $this->line(" - super_admin.intercept_gate: " . config('filament-shield.super_admin.intercept_gate'));
        
        // 10. Verificar si hay políticas registradas para recursos clave
        $this->info("Políticas registradas para recursos clave:");
        $policies = app('Illuminate\Contracts\Auth\Access\Gate')->policies();
        
        $keyModels = [
            'App\Models\User',
            'Spatie\Permission\Models\Role',
        ];
        
        foreach ($keyModels as $model) {
            if (isset($policies[$model])) {
                if (is_object($policies[$model])) {
                    $this->info(" - {$model}: " . get_class($policies[$model]));
                } else {
                    $this->info(" - {$model}: " . (is_string($policies[$model]) ? $policies[$model] : 'Tipo desconocido'));
                }
            } else {
                $this->error(" - {$model}: No tiene política registrada");
            }
        }
        
        return Command::SUCCESS;
    }
}
