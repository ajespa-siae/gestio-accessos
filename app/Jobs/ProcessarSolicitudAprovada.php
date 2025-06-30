<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SolicitudAcces;
use App\Models\ChecklistTask;
use App\Models\User;
use App\Jobs\NotificarAprovacioFinal;
use Illuminate\Support\Facades\Log;

class ProcessarSolicitudAprovada implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SolicitudAcces $solicitud
    ) {}

    public function handle(): void
    {
        Log::info("Processant sol·licitud aprovada: {$this->solicitud->identificador_unic}");
        
        foreach ($this->solicitud->sistemesSolicitats as $sistemaSol) {
            $this->crearTascaIT($sistemaSol);
        }
        
        // Actualitzar estat final
        $this->solicitud->update([
            'estat' => 'finalitzada',
            'data_finalitzacio' => now()
        ]);
        
        // Notificar aprovació final
        NotificarAprovacioFinal::dispatch($this->solicitud);
        
        Log::info("Sol·licitud processada: {$this->solicitud->identificador_unic}");
    }
    
    private function crearTascaIT($sistemaSol): void
    {
        $sistema = $sistemaSol->sistema;
        $nivell = $sistemaSol->nivellAcces;
        $empleat = $this->solicitud->empleatDestinatari;
        
        // Buscar usuari IT per assignar tasca
        $usuariIT = $this->trobarUsuariIT($empleat->departament_id);
        
        if (!$usuariIT) {
            Log::warning("No s'ha trobat usuari IT per assignar tasca de {$sistema->nom}");
            return;
        }
        
        ChecklistTask::create([
            'checklist_instance_id' => null, // Tasca independent, no vinculada a checklist
            'nom' => "Assignar accés: {$sistema->nom}",
            'descripcio' => "Nivell d'accés: {$nivell->nom}\nEmpleat: {$empleat->nom_complet}\nDepartament: {$empleat->departament->nom}",
            'ordre' => 1,
            'obligatoria' => true,
            'completada' => false,
            'data_assignacio' => now(),
            'usuari_assignat_id' => $usuariIT->id,
            'observacions' => "Sol·licitud: {$this->solicitud->identificador_unic}"
        ]);
        
        Log::info("Tasca IT creada per {$sistema->nom} assignada a {$usuariIT->name}");
    }
    
    private function trobarUsuariIT(int $departamentId): ?User
    {
        // Prioritzar usuaris IT del mateix departament
        $usuariIT = User::where('rol_principal', 'it')
                       ->where('actiu', true)
                       ->whereHas('departamentsGestionats', function ($query) use ($departamentId) {
                           $query->where('departament_id', $departamentId);
                       })
                       ->first();
        
        // Si no en troba, agafar qualsevol usuari IT
        if (!$usuariIT) {
            $usuariIT = User::where('rol_principal', 'it')
                           ->where('actiu', true)
                           ->first();
        }
        
        return $usuariIT;
    }
}