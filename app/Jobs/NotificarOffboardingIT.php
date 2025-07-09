<?php

namespace App\Jobs;

use App\Models\ChecklistInstance;
use App\Models\User;
use App\Models\Notificacio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificarOffboardingIT implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ChecklistInstance $checklistInstance
    ) {}

    public function handle(): void
    {
        try {
            // Obtener usuarios con rol 'it' usando el sistema de roles de Shield
            $usuarisIT = User::whereHas('roles', function($query) {
                    $query->where('name', 'it');
                })
                ->where('actiu', true)
                ->get();
                
            // Fallback: si no hay usuarios con rol 'it' en Shield, intentar con el campo rol_principal
            if ($usuarisIT->isEmpty()) {
                Log::info('No se encontraron usuarios con rol "it" en Shield, intentando con rol_principal');
                $usuarisIT = User::where('rol_principal', 'it')
                               ->where('actiu', true)
                               ->get();
            }

            if ($usuarisIT->isEmpty()) {
                Log::warning('No hi ha usuaris IT actius per notificar offboarding');
                return;
            }

            $empleat = $this->checklistInstance->empleat;

            foreach ($usuarisIT as $usuariIT) {
                Notificacio::crear(
                    $usuariIT->id,
                    'Nova checklist d\'offboarding assignada',
                    "Empleat/da de baixa: {$empleat->nom_complet} ({$empleat->departament->nom})\nData baixa: {$empleat->data_baixa->format('d/m/Y')}",
                    'warning',
                    "/admin/checklist-instances/{$this->checklistInstance->id}",
                    $empleat->identificador_unic
                );
            }

            Log::info("Notificacions d'offboarding enviades a {$usuarisIT->count()} usuaris IT");

        } catch (\Exception $e) {
            Log::error("Error notificant offboarding IT: " . $e->getMessage());
            throw $e;
        }
    }
}