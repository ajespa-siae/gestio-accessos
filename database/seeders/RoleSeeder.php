<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear tots els rols bàsics del sistema
        $roles = [
            'super_admin' => 'Super Administrador - Accés complet al sistema',
            'admin' => 'Administrador - Gestió general del sistema',
            'rrhh' => 'Recursos Humans - Gestió d\'empleats i processos RRHH',
            'gestor' => 'Gestor de Departament - Gestió del seu departament',
            'it' => 'Departament IT - Gestió tècnica i sistemes',
        ];

        foreach ($roles as $roleName => $description) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['name' => $roleName, 'guard_name' => 'web']
            );
            
            $this->command->info("Rol creat/verificat: {$roleName} - {$description}");
        }
        
        $this->command->info('Tots els rols bàsics del sistema han estat creats correctament.');
    }
}
