<?php

namespace App\Jobs;

use App\Models\ChecklistInstance;
use App\Models\Notificacio;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessarChecklistCompletada implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ChecklistInstance $checklistInstance
    ) {}

    public function handle(): void
    {
        try {
            $empleat = $this->checklistInstance->empleat;
            $tipus = $this->checklistInstance->getTipusTemplate();

            Log::info("Checklist {$tipus} completada per l'empleat {$empleat->identificador_unic}");

            if ($tipus === 'onboarding') {
                $this->processarOnboardingCompletat();
            } elseif ($tipus === 'offboarding') {
                $this->processarOffboardingCompletat();
            }

        } catch (\Exception $e) {
            Log::error("Error processant checklist completada: " . $e->getMessage());
            throw $e;
        }
    }

    private function processarOnboardingCompletat(): void
    {
        $empleat = $this->checklistInstance->empleat;

        // Notificar RRHH que l'onboarding està completat utilizando Shield
        $usuarisRRHH = User::whereHas('roles', function($query) {
                $query->where('name', 'rrhh');
            })
            ->where('actiu', true)
            ->get();
            
        // Fallback: si no hay usuarios con rol 'rrhh' en Shield, intentar con el campo rol_principal
        if ($usuarisRRHH->isEmpty()) {
            $usuarisRRHH = User::where('rol_principal', 'rrhh')
                             ->where('actiu', true)
                             ->get();
        }

        foreach ($usuarisRRHH as $usuariRRHH) {
            Notificacio::crear(
                $usuariRRHH->id,
                'Onboarding completat',
                "L'empleat {$empleat->nom_complet} ja pot rebre sol·licituds d'accés.",
                'success',
                "/admin/empleats/{$empleat->id}",
                $empleat->identificador_unic
            );
        }

        // Notificar gestor del departament
        $gestor = $empleat->departament->gestor;
        if ($gestor) {
            Notificacio::crear(
                $gestor->id,
                'Empleat preparat per accessos',
                "L'empleat {$empleat->nom_complet} del vostre departament ja té l'onboarding completat i pot rebre sol·licituds d'accés.",
                'info',
                "/admin/empleats/{$empleat->id}",
                $empleat->identificador_unic
            );
        }

        Log::info("Processat onboarding completat per {$empleat->identificador_unic}");
    }

    private function processarOffboardingCompletat(): void
    {
        $empleat = $this->checklistInstance->empleat;

        // Notificar RRHH que l'offboarding està completat utilizando Shield
        $usuarisRRHH = User::whereHas('roles', function($query) {
                $query->where('name', 'rrhh');
            })
            ->where('actiu', true)
            ->get();
            
        // Fallback: si no hay usuarios con rol 'rrhh' en Shield, intentar con el campo rol_principal
        if ($usuarisRRHH->isEmpty()) {
            $usuarisRRHH = User::where('rol_principal', 'rrhh')
                             ->where('actiu', true)
                             ->get();
        }

        foreach ($usuarisRRHH as $usuariRRHH) {
            Notificacio::crear(
                $usuariRRHH->id,
                'Offboarding completat',
                "El procés de baixa de {$empleat->nom_complet} s'ha completat correctament.",
                'success',
                "/admin/empleats/{$empleat->id}",
                $empleat->identificador_unic
            );
        }

        Log::info("Processat offboarding completat per {$empleat->identificador_unic}");
    }
}