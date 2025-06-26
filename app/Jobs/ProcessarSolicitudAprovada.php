<?php

namespace App\Jobs;

use App\Models\SolicitudAcces;
use App\Models\ChecklistTask;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessarSolicitudAprovada implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SolicitudAcces $solicitud
    ) {}

    public function handle(): void
    {
        try {
            // Crear tasques IT per cada sistema aprovat
            foreach ($this->solicitud->sistemesSolicitats as $sistemaSolicitat) {
                $this->crearTascaAssignacioAcces($sistemaSolicitat);
            }

            Log::info("Tasques IT creades per sol·licitud aprovada {$this->solicitud->identificador_unic}");

            // Notificar aprovació final
            NotificarAprovacioFinal::dispatch($this->solicitud);

        } catch (\Exception $e) {
            Log::error("Error processant sol·licitud aprovada {$this->solicitud->identificador_unic}: " . $e->getMessage());
            throw $e;
        }
    }

    private function crearTascaAssignacioAcces($sistemaSolicitat): void
    {
        $sistema = $sistemaSolicitat->sistema;
        $nivellAcces = $sistemaSolicitat->nivellAcces;
        $empleat = $this->solicitud->empleatDestinatari;

        // Trobar usuari IT per assignar
        $usuariIT = $this->trobarUsuariIT();

        $tasca = ChecklistTask::create([
            'checklist_instance_id' => null, // Tasca independent
            'nom' => "Assignar accés: {$sistema->nom}",
            'descripcio' => "Empleat: {$empleat->nom_complet}\nNivell: {$nivellAcces->nom}\nJustificació: {$this->solicitud->justificacio}",
            'ordre' => 1,
            'obligatoria' => true,
            'completada' => false,
            'data_assignacio' => now(),
            'data_limit' => now()->addDays(3), // 3 dies per completar
            'usuari_assignat_id' => $usuariIT?->id,
            'observacions' => "Sol·licitud: {$this->solicitud->identificador_unic}"
        ]);

        Log::info("Tasca d'assignació d'accés creada: {$tasca->nom}");
    }

    private function trobarUsuariIT(): ?User
    {
        return User::where('rol_principal', 'it')
                   ->where('actiu', true)
                   ->first();
    }
}