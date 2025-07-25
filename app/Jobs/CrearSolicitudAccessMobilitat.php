<?php

namespace App\Jobs;

use App\Models\ProcessMobilitat;
use App\Models\SolicitudAcces;
use App\Models\SolicitudSistema;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrearSolicitudAccessMobilitat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private ProcessMobilitat $processMobilitat
    ) {}

    public function handle(): void
    {
        try {
            // Crear sol·licitud d'accés
            $solicitud = SolicitudAcces::create([
                'identificador_unic' => SolicitudAcces::generarIdentificador(),
                'empleat_destinatari_id' => $this->processMobilitat->empleat_id,
                'usuari_solicitant_id' => $this->processMobilitat->usuari_solicitant_id,
                'estat' => 'pendent',
                'tipus' => 'mobilitat',
                'data_inici_necessaria' => now()->addDays(1),
                'justificacio' => "Mobilitat: {$this->processMobilitat->justificacio}",
                'process_mobilitat_id' => $this->processMobilitat->id
            ]);

            // Crear sistemes sol·licitats basats en les decisions dels departaments
            foreach ($this->processMobilitat->sistemes as $sistemaMobilitat) {
                // Calcular estat final
                $estatFinal = $sistemaMobilitat->calcularEstatFinal();
                $sistemaMobilitat->update(['estat_final' => $estatFinal]);

                // Només crear sol·licituds per sistemes que s'afegeixen o modifiquen
                if (in_array($estatFinal, ['afegir', 'modificar'])) {
                    SolicitudSistema::create([
                        'solicitud_acces_id' => $solicitud->id,
                        'sistema_id' => $sistemaMobilitat->sistema_id,
                        'nivell_acces_id' => $sistemaMobilitat->nivell_acces_final_id,
                        'aprovat' => false // Necessita validació
                    ]);
                }
            }

            // Actualitzar el procés de mobilitat
            $this->processMobilitat->update([
                'solicitud_acces_id' => $solicitud->id,
                'estat' => 'validant'
            ]);

            Log::info("Sol·licitud d'accés creada per mobilitat: {$solicitud->identificador_unic}");

            // Disparar validacions automàticament
            dispatch(new \App\Jobs\CrearValidacionsSolicitud($solicitud));

        } catch (\Exception $e) {
            Log::error("Error creant sol·licitud d'accés per mobilitat: {$e->getMessage()}");
            throw $e;
        }
    }
}
