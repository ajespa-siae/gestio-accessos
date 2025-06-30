<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Departament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin principal
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador Sistema',
                'email' => 'admin@esparreguera.cat',
                'password' => Hash::make('admin123'),
                'username' => 'admin',
                'nif' => '00000000A',
                'rol_principal' => 'admin',
                'actiu' => true,
                'ldap_managed' => false,
                'email_verified_at' => now()
            ]
        );

        // Usuari RRHH
        User::factory()->create([
            'name' => 'Gestor RRHH',
            'email' => 'rrhh@esparreguera.cat',
            'username' => 'rrhh.user',
            'rol_principal' => 'rrhh',
        ]);

        // Usuaris IT
        User::factory()->count(2)->it()->create();

        // Gestors de departament
        $gestors = User::factory()->count(3)->gestor()->create();

        // Assignar gestors a departaments si existeixen
        $departaments = Departament::limit(3)->get();
        foreach ($gestors as $index => $gestor) {
            if (isset($departaments[$index])) {
                $gestor->departamentsGestionats()->attach($departaments[$index]->id, [
                    'gestor_principal' => true
                ]);
            }
        }

        // Empleats normals
        User::factory()->count(10)->create();

        $this->command->info('✅ Usuaris creats correctament');
    }
}
