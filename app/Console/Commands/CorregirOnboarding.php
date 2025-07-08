<?php

namespace App\Console\Commands;

use App\Models\Empleat;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CorregirOnboarding extends Command
{
    protected $signature = 'app:corregir-onboarding {empleat_id}';
    protected $description = 'Corrige el proceso de onboarding para un empleado específico';

    public function handle()
    {
        $empleatId = $this->argument('empleat_id');
        $empleat = Empleat::find($empleatId);

        if (!$empleat) {
            $this->error("No se encontró el empleado con ID {$empleatId}");
            return 1;
        }

        $this->info("Corrigiendo onboarding para: {$empleat->nom_complet} (ID: {$empleat->id})");
        
        try {
            // Buscar plantilla global de onboarding
            $template = ChecklistTemplate::where('tipus', 'onboarding')
                ->where('actiu', true)
                ->whereNull('departament_id')
                ->first();
                
            if (!$template) {
                $this->warn("No se encontró plantilla global de onboarding, creando una básica...");
                
                // Crear plantilla básica
                $template = ChecklistTemplate::create([
                    'nom' => 'Onboarding Bàsic',
                    'departament_id' => $empleat->departament_id,
                    'tipus' => 'onboarding',
                    'actiu' => true
                ]);
                
                // Tareas básicas
                $tasquesBasiques = [
                    [
                        'nom' => 'Crear usuari LDAP',
                        'descripcio' => 'Crear compte LDAP per al nou empleat',
                        'ordre' => 1,
                        'rol_assignat' => 'it',
                        'activa' => true,
                        'obligatoria' => true
                    ],
                    [
                        'nom' => 'Crear compte de correu',
                        'descripcio' => 'Configurar correu corporatiu',
                        'ordre' => 2,
                        'rol_assignat' => 'it',
                        'activa' => true,
                        'obligatoria' => true
                    ],
                    [
                        'nom' => 'Preparar equip informàtic',
                        'descripcio' => 'Assignar ordinador i perifèrics',
                        'ordre' => 3,
                        'rol_assignat' => 'it',
                        'activa' => true,
                        'obligatoria' => true
                    ]
                ];
                
                foreach ($tasquesBasiques as $tasca) {
                    $template->tasquesTemplate()->create($tasca);
                }
                
                $this->info("Plantilla básica creada con ID: {$template->id}");
            }
            
            // Crear instancia de checklist manualmente
            $instance = new ChecklistInstance();
            $instance->template_id = $template->id;
            $instance->empleat_id = $empleat->id;
            $instance->estat = 'pendent';
            $instance->save();
            
            $this->info("Instancia de checklist creada con ID: {$instance->id}");
            
            // Crear tareas desde las tareas de la plantilla
            foreach ($template->tasquesTemplate()->where('activa', true)->get() as $tasca) {
                // Ya no asignamos usuarios específicos a las tareas
                // Solo mantenemos el rol_assignat para que cualquier usuario con ese rol pueda completarla
                
                $instance->tasques()->create([
                    'nom' => $tasca->nom,
                    'descripcio' => $tasca->descripcio,
                    'ordre' => $tasca->ordre,
                    'obligatoria' => $tasca->obligatoria ?? true,
                    'data_limit' => $tasca->dies_limit ? 
                        now()->addDays($tasca->dies_limit) : null,
                    'rol_assignat' => $tasca->rol_assignat,
                    'usuari_assignat_id' => null
                ]);
            }
            
            $this->info("Tareas creadas correctamente");
            
            // Notificar a usuarios IT
            $this->info("Notificando a usuarios IT...");
            
            $usuarisIT = \App\Models\User::whereHas('roles', function($query) {
                $query->where('name', 'it');
            })->where('actiu', true)->get();
            
            if ($usuarisIT->isEmpty()) {
                $usuarisIT = \App\Models\User::where('rol_principal', 'it')
                    ->where('actiu', true)
                    ->get();
            }
            
            foreach ($usuarisIT as $usuariIT) {
                \App\Models\Notificacio::crear(
                    $usuariIT->id,
                    "Nova checklist onboarding assignada",
                    "Empleat: {$empleat->nom_complet} ({$empleat->departament->nom})",
                    'info',
                    "/admin/checklist-instances/{$instance->id}",
                    $empleat->identificador_unic
                );
            }
            
            $this->info("Notificaciones enviadas a " . $usuarisIT->count() . " usuarios IT");
            
            Log::info("Checklist d'onboarding creada manualment per l'empleat {$empleat->identificador_unic}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error al corregir onboarding: " . $e->getMessage());
            Log::error("Error corregint onboarding per {$empleat->identificador_unic}: " . $e->getMessage());
            return 1;
        }
    }
}
