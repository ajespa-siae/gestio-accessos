<?php

namespace App\Jobs;

use App\Models\Empleat;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistInstance;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class CrearChecklistOffboarding
{
    use Dispatchable;

    public function __construct(
        public Empleat $empleat
    ) {}

    public function handle(): void
    {
        try {
            // Buscar template d'offboarding
            $template = ChecklistTemplate::where('tipus', 'offboarding')
                ->where('actiu', true)
                ->where(function($q) {
                    $q->where('departament_id', $this->empleat->departament_id)
                      ->orWhereNull('departament_id');
                })
                ->orderByRaw('departament_id IS NULL')
                ->first();

            if (!$template) {
                $this->crearTemplateOffboardingBasic();
                return;
            }

            $instance = $template->crearInstancia($this->empleat);

            // Marcar sol·licituds d'accés per revocació
            $this->marcarAccessosPerRevocar();

            Log::info("Checklist d'offboarding creada per l'empleat {$this->empleat->identificador_unic}");

            // Notificar IT i gestors
            NotificarOffboardingIT::dispatch($instance);
            NotificarGestorBaixa::dispatch($this->empleat);

        } catch (\Exception $e) {
            Log::error("Error creant checklist offboarding per {$this->empleat->identificador_unic}: " . $e->getMessage());
            throw $e;
        }
    }

    private function marcarAccessosPerRevocar(): void
    {
        // Obtenir totes les sol·licituds d'accés actives de l'empleat
        $solicitudsActives = $this->empleat->solicitudsAcces()
            ->whereIn('estat', ['aprovada', 'finalitzada'])
            ->with(['sistemesSolicitats.sistema', 'sistemesSolicitats.nivellAcces'])
            ->get();
            
        if ($solicitudsActives->isEmpty()) {
            Log::info("No hi ha accessos actius per revocar per {$this->empleat->identificador_unic}");
            return;
        }
        
        $tasquesCreades = 0;
        
        foreach ($solicitudsActives as $solicitud) {
            foreach ($solicitud->sistemesSolicitats as $sistemaSol) {
                $sistema = $sistemaSol->sistema;
                $nivell = $sistemaSol->nivellAcces;
                
                // Crear tasca de revocació per cada sistema
                \App\Models\ChecklistTask::create([
                    'checklist_instance_id' => null, // Tasca independent
                    'solicitud_acces_id' => $solicitud->id,
                    'nom' => "Revocar accés: {$sistema->nom}",
                    'descripcio' => "REVOCACIÓ PER BAIXA EMPLEAT\n" .
                                   "Empleat: {$this->empleat->nom_complet}\n" .
                                   "Departament: {$this->empleat->departament->nom}\n" .
                                   "Nivell d'accés: {$nivell->nom}\n" .
                                   "Data baixa: {$this->empleat->data_baixa->format('d/m/Y')}\n" .
                                   "Sol·licitud original: {$solicitud->identificador_unic}",
                    'ordre' => 1,
                    'obligatoria' => true,
                    'completada' => false,
                    'data_assignacio' => now(),
                    'usuari_assignat_id' => null, // Assignació manual posterior
                    'rol_assignat' => $sistema->rol_gestor_defecte ?? 'it',
                    'observacions' => "Revocació automàtica per baixa empleat: {$this->empleat->identificador_unic}"
                ]);
                
                $tasquesCreades++;
            }
        }
        
        Log::info("Creades {$tasquesCreades} tasques de revocació per {$this->empleat->identificador_unic}");
    }

    private function crearTemplateOffboardingBasic(): void
    {
        $template = ChecklistTemplate::create([
            'nom' => 'Offboarding Bàsic',
            'departament_id' => $this->empleat->departament_id,
            'tipus' => 'offboarding',
            'actiu' => true
        ]);

        $tasquesOffboarding = [
            [
                'nom' => 'Revocar accessos als sistemes',
                'descripcio' => 'Eliminar permisos de tots els sistemes',
                'ordre' => 1,
                'rol_assignat' => 'it'
            ],
            [
                'nom' => 'Recuperar equipament',
                'descripcio' => 'Recollir ordinador, telèfon i altres equips',
                'ordre' => 2,
                'rol_assignat' => 'it'
            ],
            [
                'nom' => 'Netejar comptes LDAP/correu',
                'descripcio' => 'Desactivar comptes i fer backup',
                'ordre' => 3,
                'rol_assignat' => 'it'
            ],
            [
                'nom' => 'Gestionar documentació RRHH',
                'descripcio' => 'Arxivar documentació i liquidació',
                'ordre' => 4,
                'rol_assignat' => 'rrhh'
            ]
        ];

        foreach ($tasquesOffboarding as $tasca) {
            $template->tasquesTemplate()->create($tasca);
        }

        $instance = $template->crearInstancia($this->empleat);
        NotificarOffboardingIT::dispatch($instance);
    }
}