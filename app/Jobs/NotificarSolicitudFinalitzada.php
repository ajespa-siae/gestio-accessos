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
use Illuminate\Support\Facades\Mail;

class NotificarSolicitudFinalitzada implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SolicitudAcces $solicitud
    ) {}

    public function handle(): void
    {
        try {
            $usuariSolicitant = $this->solicitud->usuariSolicitant;
            $empleat = $this->solicitud->empleatDestinatari;
            
            if (!$usuariSolicitant) {
                Log::warning("No es pot notificar: Sol·licitud {$this->solicitud->identificador_unic} no té usuari sol·licitant");
                return;
            }
            
            // Crear notificació interna
            Notificacio::create([
                'user_id' => $usuariSolicitant->id,
                'titol' => 'Sol·licitud d\'accés finalitzada',
                'missatge' => "La sol·licitud per {$empleat->nom_complet} ha estat completada i finalitzada.",
                'tipus' => 'success',
                'llegida' => false,
                'url_accio' => "/admin/solicituds-acces/{$this->solicitud->id}",
                'identificador_relacionat' => $this->solicitud->identificador_unic
            ]);
            
            // Enviar email si l'usuari té correu
            if ($usuariSolicitant->email) {
                $this->enviarEmail($usuariSolicitant);
            }
            
            Log::info("Notificació de finalització enviada per sol·licitud {$this->solicitud->identificador_unic} a {$usuariSolicitant->name}");
            
        } catch (\Exception $e) {
            Log::error("Error notificant finalització de sol·licitud: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function enviarEmail($usuari): void
    {
        try {
            $solicitud = $this->solicitud;
            $empleat = $solicitud->empleatDestinatari;
            
            $data = [
                'solicitud' => $solicitud,
                'empleat' => $empleat,
                'usuari' => $usuari,
            ];
            
            Mail::send('emails.solicitud-finalitzada', $data, function ($message) use ($usuari, $solicitud) {
                $message->to($usuari->email)
                    ->subject("[SIAE] Sol·licitud finalitzada: {$solicitud->identificador_unic}")
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            Log::info("Email de finalització enviat a {$usuari->email} per sol·licitud {$solicitud->identificador_unic}");
            
        } catch (\Exception $e) {
            Log::error("Error enviant email de finalització: " . $e->getMessage());
        }
    }
}
