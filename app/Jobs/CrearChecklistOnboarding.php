<?php

namespace App\Jobs;

use App\Models\Empleat;
use App\Models\ChecklistTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrearChecklistOnboarding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Empleat $empleat
    ) {}

    public function handle(): void
    {
        try {
            // Buscar template (departament específic o global)
            $template = ChecklistTemplate::where('tipus', 'onboarding')
                ->where('actiu', true)
                ->where(function($q) {
                    $q->where('departament_id', $this->empleat->departament_id)
                      ->orWhereNull('departament_id');
                })
                ->orderByRaw('departament_id IS NULL') // Departament específic primer
                ->first();

            if (!$template) {
                Log::warning("No s'ha trobat template d'onboarding per l'empleat {$this->empleat->identificador_unic}");
                $this->crearTemplateBasic();
                return;
            }

            // Crear instància de checklist
            $instance = $template->crearInstancia($this->empleat);

            Log::info("Checklist d'onboarding creada per l'empleat {$this->empleat->identificador_unic}");

            // Notificar usuaris IT
            NotificarNovaChecklistIT::dispatch($instance);

        } catch (\Exception $e) {
            Log::error("Error creant checklist onboarding per {$this->empleat->identificador_unic}: " . $e->getMessage());
            throw $e;
        }
    }

    private function crearTemplateBasic(): void
    {
        $template = ChecklistTemplate::create([
            'nom' => 'Onboarding Bàsic',
            'departament_id' => $this->empleat->departament_id,
            'tipus' => 'onboarding',
            'actiu' => true
        ]);

        // Tasques bàsiques
        $tasquesBasiques = [
            [
                'nom' => 'Crear usuari LDAP',
                'descripcio' => 'Crear compte LDAP per al nou empleat',
                'ordre' => 1,
                'rol_assignat' => 'it'
            ],
            [
                'nom' => 'Crear compte de correu',
                'descripcio' => 'Configurar correu corporatiu',
                'ordre' => 2,
                'rol_assignat' => 'it'
            ],
            [
                'nom' => 'Preparar equip informàtic',
                'descripcio' => 'Assignar ordinador i perifèrics',
                'ordre' => 3,
                'rol_assignat' => 'it'
            ]
        ];

        foreach ($tasquesBasiques as $tasca) {
            $template->tasquesTemplate()->create($tasca);
        }

        // Crear instància
        $instance = $template->crearInstancia($this->empleat);
        NotificarNovaChecklistIT::dispatch($instance);
    }
}