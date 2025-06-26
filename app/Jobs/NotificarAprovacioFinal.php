<?php

namespace App\Jobs;

use App\Models\SolicitudAcces;
use App\Models\Notificacio;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificarAprovacioFinal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SolicitudAcces $solicitud
    ) {}

    public function handle(): void
    {
        try {
            $empleat = $this->solicitud->empleatDestinatari;
            $usuariSolicitant = $this->solicitud->usuariSolicitant;

            // Notificar usuari que va fer la sol·licitud
            Notificacio::crear(
                $usuariSolicitant->id,
                'Sol·licitud d\'accés aprovada',
                "La sol·licitud per {$empleat->nom_complet} ha estat aprovada i enviada a IT per implementar.",
                'success',
                "/admin/solicituds-acces/{$this->solicitud->id}",
                $this->solicitud->identificador_unic
            );

            // Notificar gestor del departament si no és el mateix que va fer la sol·licitud
            $gestor = $empleat->departament->gestor;
            if ($gestor && $gestor->id !== $usuariSolicitant->id) {
                Notificacio::crear(
                    $gestor->id,
                    'Sol·licitud d\'accés aprovada',
                    "La sol·licitud d'accés per l'empleat {$empleat->nom_complet} ha estat aprovada.",
                    'success',
                    "/admin/solicituds-acces/{$this->solicitud->id}",
                    $this->solicitud->identificador_unic
                );
            }

            // Notificar usuaris RRHH
            $usuarisRRHH = User::where('rol_principal', 'rrhh')
                             ->where('actiu', true)
                             ->get();

            foreach ($usuarisRRHH as $usuariRRHH) {
                Notificacio::crear(
                    $usuariRRHH->id,
                    'Sol·licitud processada',
                    "Sol·licitud {$this->solicitud->identificador_unic} aprovada i enviada a IT per implementar.\nEmpleat: {$empleat->nom_complet}",
                    'info',
                    "/admin/solicituds-acces/{$this->solicitud->id}",
                    $this->solicitud->identificador_unic
                );
            }

            // Notificar usuaris IT
            $usuarisIT = User::where('rol_principal', 'it')
                           ->where('actiu', true)
                           ->get();

            foreach ($usuarisIT as $usuariIT) {
                $sistemes = $this->solicitud->sistemesSolicitats->map(function($ss) {
                    return $ss->sistema->nom . ' (' . $ss->nivellAcces->nom . ')';
                })->implode(', ');

                Notificacio::crear(
                    $usuariIT->id,
                    'Nous accessos per implementar',
                    "Empleat: {$empleat->nom_complet}\nSistemes: {$sistemes}",
                    'warning',
                    "/admin/checklist-tasks",
                    $this->solicitud->identificador_unic
                );
            }

            Log::info("Notificacions d'aprovació final enviades per sol·licitud {$this->solicitud->identificador_unic}");

        } catch (\Exception $e) {
            Log::error("Error notificant aprovació final: " . $e->getMessage());
            throw $e;
        }
    }
}