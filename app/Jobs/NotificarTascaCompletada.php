<?php

namespace App\Jobs;

use App\Models\ChecklistTask;
use App\Models\Notificacio;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificarTascaCompletada implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ChecklistTask $task
    ) {}

    public function handle(): void
    {
        try {
            $usuariCompletat = $this->task->usuariCompletat;
            $checklistInstance = $this->task->checklistInstance;

            if (!$checklistInstance) {
                Log::info("Tasca independent completada: {$this->task->nom}");
                return;
            }

            $empleat = $checklistInstance->empleat;
            $tipus = $checklistInstance->getTipusTemplate();

            // Notificar usuaris RRHH
            $usuarisRRHH = User::where('rol_principal', 'rrhh')
                             ->where('actiu', true)
                             ->get();

            foreach ($usuarisRRHH as $usuariRRHH) {
                Notificacio::crear(
                    $usuariRRHH->id,
                    "Tasca {$tipus} completada",
                    "Tasca: {$this->task->nom}\nEmpleat: {$empleat->nom_complet}\nCompletat per: {$usuariCompletat->name}",
                    'success',
                    "/admin/checklist-instances/{$checklistInstance->id}",
                    $empleat->identificador_unic
                );
            }

            // Si la checklist està completada, notificar també
            if ($checklistInstance->estaCompletada()) {
                ProcessarChecklistCompletada::dispatch($checklistInstance);
            }

            Log::info("Notificacions enviades per tasca completada: {$this->task->nom}");

        } catch (\Exception $e) {
            Log::error("Error notificant tasca completada: " . $e->getMessage());
            throw $e;
        }
    }
}