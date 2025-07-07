<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MigrateRolesToShield extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-roles-to-shield';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra los roles existentes al sistema de Shield';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando migración de roles a Shield...');
        
        // Crear los roles si no existen
        $roles = ['admin', 'rrhh', 'it', 'gestor', 'empleat'];
        $createdRoles = [];
        
        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $createdRoles[] = $roleName;
            $this->info("Rol '{$roleName}' creado o verificado.");
        }
        
        $this->info('Roles creados: ' . implode(', ', $createdRoles));
        
        // Asignar roles a usuarios basados en su rol_principal
        $this->info('Asignando roles a usuarios...');
        $count = 0;
        
        User::all()->each(function ($user) use (&$count) {
            if ($user->rol_principal) {
                // Asignar el rol principal
                $user->assignRole($user->rol_principal);
                $count++;
                
                // Aquí podrías implementar lógica adicional para asignar múltiples roles
                // basándote en alguna otra fuente de datos o reglas de negocio
            }
        });
        
        $this->info("Se han asignado roles a {$count} usuarios.");
        $this->info('Migración completada con éxito.');
        
        return Command::SUCCESS;
    }
}
