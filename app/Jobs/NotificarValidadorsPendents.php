<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SolicitudAcces;
use App\Models\Notificacio;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificarValidadorsPendents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SolicitudAcces $solicitud
    ) {}

    public function handle(): void
    {
        Log::info("Notificant validadors per solicitud: {$this->solicitud->identificador_unic}");
        
        foreach ($this->solicitud->validacions()->where('estat', 'pendent')->get() as $validacio) {
            if ($validacio->tipus_validacio === 'individual') {
                $this->notificarValidadorIndividual($validacio);
            } elseif ($validacio->tipus_validacio === 'grup') {
                $this->notificarGrupValidadors($validacio);
            }
        }
    }
    
    private function notificarValidadorIndividual($validacio): void
    {
        $validador = $validacio->validador;
        if (!$validador) {
            Log::warning("Validador no trobat per validació {$validacio->id}");
            return;
        }
        
        $this->enviarNotificacio($validador, $validacio, 'individual');
    }
    
    private function notificarGrupValidadors($validacio): void
    {
        // Manejar el campo grup_validadors_ids que puede ser un array o una cadena JSON
        $gestorsIds = $validacio->grup_validadors_ids;
        if (is_string($gestorsIds)) {
            $gestorsIds = json_decode($gestorsIds, true);
        }
        
        if (empty($gestorsIds)) {
            Log::warning("No hi ha gestors configurats per validació grup {$validacio->id}");
            return;
        }
        
        $gestors = User::whereIn('id', $gestorsIds)->where('actiu', true)->get();
        
        foreach ($gestors as $gestor) {
            $this->enviarNotificacio($gestor, $validacio, 'grup');
        }
        
        Log::info("Notificació enviada a {$gestors->count()} gestors per validació grup {$validacio->id}");
    }
    
    private function enviarNotificacio(User $validador, $validacio, string $tipus): void
    {
        $sistema = $validacio->sistema;
        $empleat = $this->solicitud->empleatDestinatari;
        
        // Personalitzar missatge segons tipus
        if ($tipus === 'grup') {
            $titol = 'Nova sol·licitud d\'accés per validar (grup)';
            $missatge = "S'ha sol·licitat un accés per a l'empleat/da {$empleat->nom_complet} al sistema {$sistema->nom}. "
                      . "Qualsevol gestor del vostre departament pot validar aquesta sol·licitud.";
        } else {
            $titol = 'Nova sol·licitud d\'accés per validar';
            $missatge = "S'ha sol·licitat un accés per a l'empleat/da {$empleat->nom_complet} al sistema {$sistema->nom}";
        }
        
        // Email (si està configurat)
        if ($validador->email) {
            try {
                Mail::to($validador->email)->send(new \App\Mail\SolicitudPendentMail($this->solicitud, $validacio));
                Log::info("Email enviat a {$validador->email} per validació de {$sistema->nom}");
            } catch (\Exception $e) {
                Log::error("Error enviant email a {$validador->email}: " . $e->getMessage());
            }
        }
        
        // Notificació in-app
        Notificacio::create([
            'user_id' => $validador->id,
            'titol' => $titol,
            'missatge' => $missatge,
            'tipus' => 'warning',
            'url_accio' => "/validacions/{$validacio->id}",
            'identificador_relacionat' => $this->solicitud->identificador_unic
        ]);
        
        Log::info("Notificació creada per {$validador->name}");
    }
}