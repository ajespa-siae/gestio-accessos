<?php

namespace App\Jobs;

use App\Models\SolicitudAcces;
use App\Models\Notificacio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificarValidadorsPendents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SolicitudAcces $solicitud
    ) {}

    public function handle(): void
    {
        try {
            $validacionsPendents = $this->solicitud->validacionsPendents;

            if ($validacionsPendents->isEmpty()) {
                Log::warning("No hi ha validacions pendents per la sol·licitud {$this->solicitud->identificador_unic}");
                return;
            }

            foreach ($validacionsPendents as $validacio) {
                $validador = $validacio->validador;
                $sistema = $validacio->sistema;
                $empleat = $this->solicitud->empleatDestinatari;

                // Crear notificació in-app
                Notificacio::crear(
                    $validador->id,
                    'Nova sol·licitud d\'accés per validar',
                    "Sistema: {$sistema->nom}\nEmpleat: {$empleat->nom_complet}\nDepartament: {$empleat->departament->nom}",
                    'warning',
                    "/admin/validacions/{$validacio->id}",
                    $this->solicitud->identificador_unic
                );

                // TODO: Enviar email quan estigui configurat
            }

            Log::info("Notificacions enviades a {$validacionsPendents->count()} validadors per sol·licitud {$this->solicitud->identificador_unic}");

        } catch (\Exception $e) {
            Log::error("Error notificant validadors: " . $e->getMessage());
            throw $e;
        }
    }
}