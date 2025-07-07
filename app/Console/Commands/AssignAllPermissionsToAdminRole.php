<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignAllPermissionsToAdminRole extends Command
{
    protected $signature = 'shield:assign-all-permissions-admin';
    protected $description = 'Asigna todos los permisos al rol admin';

    public function handle()
    {
        $this->info('Asignando todos los permisos al rol admin...');
        
        // Obtener el rol admin
        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            $this->error('El rol admin no existe. Creándolo...');
            $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        }
        
        // Obtener todos los permisos
        $permissions = Permission::all();
        
        // Asignar todos los permisos al rol admin
        $adminRole->syncPermissions($permissions);
        
        $this->info('Se han asignado ' . $permissions->count() . ' permisos al rol admin.');
        
        return Command::SUCCESS;
    }
}
