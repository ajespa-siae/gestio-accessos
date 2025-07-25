<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class ProcessMobilitatPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear o obtenir rols
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $rrhhRole = Role::firstOrCreate(['name' => 'rrhh', 'guard_name' => 'web']);
        $gestorRole = Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
        
        // Permisos per ProcessMobilitat (RRHH)
        $rrhhPermissions = [
            'view_any_process::mobilitat',
            'view_process::mobilitat',
            'create_process::mobilitat',
            'update_process::mobilitat',
            'restore_process::mobilitat',
            'restore_any_process::mobilitat',
            'replicate_process::mobilitat',
            'reorder_process::mobilitat',
        ];
        
        // Permisos per ProcessMobilitatGestor (Gestors)
        $gestorPermissions = [
            'view_any_process::mobilitat::gestor',
            'view_process::mobilitat::gestor',
            'restore_process::mobilitat::gestor',
            'restore_any_process::mobilitat::gestor',
            'replicate_process::mobilitat::gestor',
            'reorder_process::mobilitat::gestor',
        ];
        
        // Permisos d'eliminació (només admin)
        $adminPermissions = [
            'delete_process::mobilitat',
            'delete_any_process::mobilitat',
            'force_delete_process::mobilitat',
            'force_delete_any_process::mobilitat',
            'delete_process::mobilitat::gestor',
            'delete_any_process::mobilitat::gestor',
            'force_delete_process::mobilitat::gestor',
            'force_delete_any_process::mobilitat::gestor',
        ];
        
        // Crear permisos si no existeixen i assignar-los
        $this->createAndAssignPermissions($rrhhPermissions, $rrhhRole, 'RRHH');
        $this->createAndAssignPermissions($gestorPermissions, $gestorRole, 'Gestor');
        $this->createAndAssignPermissions($adminPermissions, $adminRole, 'Admin');
        
        // Admin té tots els permisos
        $this->createAndAssignPermissions($rrhhPermissions, $adminRole, 'Admin (RRHH)');
        $this->createAndAssignPermissions($gestorPermissions, $adminRole, 'Admin (Gestor)');
        
        $this->command->info('Permisos de ProcessMobilitat assignats correctament');
    }
    
    private function createAndAssignPermissions(array $permissions, Role $role, string $roleName): void
    {
        foreach ($permissions as $permissionName) {
            // Crear el permís si no existeix
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
            
            // Assignar al rol si no el té
            if (!$role->hasPermissionTo($permissionName)) {
                $role->givePermissionTo($permission);
                Log::info("Permís {$permissionName} assignat al rol {$roleName}");
            }
        }
    }
}
