<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignAllPermissionsToSuperAdmin extends Command
{
    protected $signature = 'shield:assign-all-permissions';
    protected $description = 'Asigna todos los permisos al rol super_admin';

    public function handle()
    {
        $this->info('Asignando todos los permisos al rol super_admin...');
        
        // Obtener el rol super_admin
        $superAdminRole = Role::where('name', 'super_admin')->first();
        
        if (!$superAdminRole) {
            $this->error('El rol super_admin no existe. Creándolo...');
            $superAdminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        }
        
        // Obtener todos los permisos
        $permissions = Permission::all();
        
        // Asignar todos los permisos al rol super_admin
        $superAdminRole->syncPermissions($permissions);
        
        $this->info('Se han asignado ' . $permissions->count() . ' permisos al rol super_admin.');
        
        return Command::SUCCESS;
    }
}
