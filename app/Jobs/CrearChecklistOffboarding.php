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
        // TODO: Implementar revocació d'accessos quan estigui disponible el camp requires_revocation
        // Marcar totes les sol·licituds actives per revocació
        // $this->empleat->solicitudsAcces()
        //     ->whereIn('estat', ['aprovada', 'finalitzada'])
        //     ->update(['requires_revocation' => true]);
        
        Log::info("Accessos de {$this->empleat->identificador_unic} marcats per revocació (simulat)");
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