<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empleat;
use App\Models\Departament;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class EmpleatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Desactivar eventos para evitar que se disparen los jobs
        $eventsEnabled = Model::getEventDispatcher();
        Model::unsetEventDispatcher();
        
        // Obtener departamentos existentes
        $departaments = Departament::all();
        if ($departaments->isEmpty()) {
            $this->command->error('No hay departamentos disponibles. Ejecuta primero el seeder de departamentos.');
            return;
        }

        // Obtener usuario creador (admin)
        $usuariCreador = User::where('rol_principal', 'admin')->first();
        if (!$usuariCreador) {
            $this->command->error('No se encontró un usuario administrador. Ejecuta primero el seeder de usuarios.');
            return;
        }

        // Obtener IDs de departamentos específicos
        $recursosHumansId = $departaments->where('nom', 'Recursos Humans')->first()->id;
        $informaticaId = $departaments->where('nom', 'Informàtica')->first()->id;
        $administracioId = $departaments->where('nom', 'Administració')->first()->id;
        $serveisSocialsId = $departaments->where('nom', 'Serveis Socials')->first()->id;
        
        // Crear empleados de ejemplo
        $empleats = [
            [
                'nom_complet' => 'María López García',
                'nif' => '12345678A',
                'correu_personal' => 'mlopez@personal.com',
                'departament_id' => $recursosHumansId,
                'carrec' => 'Tècnic/a de Recursos Humans',
                'estat' => 'actiu',
                'data_alta' => now()->subMonths(6),
                'usuari_creador_id' => $usuariCreador->id,
            ],
            [
                'nom_complet' => 'Joan Martínez Soler',
                'nif' => '23456789B',
                'correu_personal' => 'jmartinez@personal.com',
                'departament_id' => $informaticaId,
                'carrec' => 'Desenvolupador/a Web',
                'estat' => 'actiu',
                'data_alta' => now()->subMonths(3),
                'usuari_creador_id' => $usuariCreador->id,
            ],
            [
                'nom_complet' => 'Anna Ferrer Puig',
                'nif' => '34567890C',
                'correu_personal' => 'aferrer@personal.com',
                'departament_id' => $administracioId,
                'carrec' => 'Administratiu/va',
                'estat' => 'actiu',
                'data_alta' => now()->subMonths(8),
                'usuari_creador_id' => $usuariCreador->id,
            ],
            [
                'nom_complet' => 'Jordi Vidal Costa',
                'nif' => '45678901D',
                'correu_personal' => 'jvidal@personal.com',
                'departament_id' => $informaticaId,
                'carrec' => 'Tècnic/a de Sistemes',
                'estat' => 'actiu',
                'data_alta' => now()->subMonths(5),
                'usuari_creador_id' => $usuariCreador->id,
            ],
            [
                'nom_complet' => 'Laura Sánchez Mir',
                'nif' => '56789012E',
                'correu_personal' => 'lsanchez@personal.com',
                'departament_id' => $recursosHumansId,
                'carrec' => 'Responsable de Selecció',
                'estat' => 'actiu',
                'data_alta' => now()->subMonths(10),
                'usuari_creador_id' => $usuariCreador->id,
            ],
            [
                'nom_complet' => 'Miquel Torres Roca',
                'nif' => '67890123F',
                'correu_personal' => 'mtorres@personal.com',
                'departament_id' => $administracioId,
                'carrec' => 'Auxiliar Administratiu/va',
                'estat' => 'baixa',
                'data_alta' => now()->subMonths(12),
                'data_baixa' => now()->subWeeks(2),
                'usuari_creador_id' => $usuariCreador->id,
                'observacions' => 'Baixa voluntària',
            ],
            [
                'nom_complet' => 'Carla Bosch Prat',
                'nif' => '78901234G',
                'correu_personal' => 'cbosch@personal.com',
                'departament_id' => $informaticaId,
                'carrec' => 'Analista Programador/a',
                'estat' => 'suspens',
                'data_alta' => now()->subMonths(4),
                'usuari_creador_id' => $usuariCreador->id,
                'observacions' => 'Suspensió temporal',
            ],
            [
                'nom_complet' => 'Pere Casals Martí',
                'nif' => '89012345H',
                'correu_personal' => 'pcasals@personal.com',
                'departament_id' => $serveisSocialsId,
                'carrec' => 'Treballador/a Social',
                'estat' => 'actiu',
                'data_alta' => now()->subMonths(7),
                'usuari_creador_id' => $usuariCreador->id,
            ],
        ];

        // Insertar empleados
        foreach ($empleats as $empleat) {
            // Generar identificador único manualmente ya que los eventos están desactivados
            $empleat['identificador_unic'] = 'EMP-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -8));
            
            // Pequeña pausa para asegurar que los identificadores sean únicos
            usleep(100000); // 0.1 segundos
            
            Empleat::create($empleat);
        }

        $this->command->info('Empleados de ejemplo creados correctamente');
        
        // Restaurar eventos
        Model::setEventDispatcher($eventsEnabled);
    }
}
