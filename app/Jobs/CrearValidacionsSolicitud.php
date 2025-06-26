<?php

namespace App\Jobs;

use App\Models\SolicitudAcces;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrearValidacionsSolicitud implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SolicitudAcces $solicitud
    ) {}

    public function handle(): void
    {
        try {
            foreach ($this->solicitud->sistemesSolicitats as $sistemaSolicitat) {
                $sistema = $sistemaSolicitat->sistema;

                // Obtenir validadors del sistema (relació pivot)
                $validadors = $sistema->getValidadorsOrdenats();

                if ($validadors->isEmpty()) {
                    Log::warning("Sistema {$sistema->nom} no té validadors configurats");
                    
                    // Buscar gestor del departament com a fallback
                    $gestorDepartament = $this->solicitud->empleatDestinatari->departament->gestor;
                    if ($gestorDepartament) {
                        $validadors = collect([$gestorDepartament]);
                    } else {
                        Log::error("No s'han trobat validadors per sistema {$sistema->nom}");
                        continue;
                    }
                }

                // Crear validacions
                foreach ($validadors as $validador) {
                    $this->solicitud->validacions()->create([
                        'sistema_id' => $sistema->id,
                        'validador_id' => $validador->id,
                        'estat' => 'pendent'
                    ]);
                }
            }

            // Actualitzar estat de la sol·licitud
            $this->solicitud->update(['estat' => 'validant']);

            Log::info("Validacions creades per la sol·licitud {$this->solicitud->identificador_unic}");

            // Notificar validadors
            NotificarValidadorsPendents::dispatch($this->solicitud);

        } catch (\Exception $e) {
            Log::error("Error creant validacions per sol·licitud {$this->solicitud->identificador_unic}: " . $e->getMessage());
            $this->solicitud->update(['estat' => 'pendent']);
            throw $e;
        }
    }
}