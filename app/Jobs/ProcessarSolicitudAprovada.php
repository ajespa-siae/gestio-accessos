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
            $this->crearTascaPerSistema($sistemaSol);
        }
        
        // Mantenir estat 'aprovada' fins que totes les tasques estiguin completades
        // L'estat canviarà a 'finalitzada' quan es completin totes les tasques
        
        // Notificar aprovació final
        NotificarAprovacioFinal::dispatch($this->solicitud);
        
        Log::info("Sol·licitud processada: {$this->solicitud->identificador_unic}");
    }
    
    private function crearTascaPerSistema($sistemaSol): void
    {
        $sistema = $sistemaSol->sistema;
        $nivell = $sistemaSol->nivellAcces;
        $empleat = $this->solicitud->empleatDestinatari;
        
        // Obtenir el rol gestor del sistema
        $rolGestor = $sistema->rol_gestor_defecte ?? 'it';
        
        ChecklistTask::create([
            'checklist_instance_id' => null, // Tasca independent, no vinculada a checklist
            'solicitud_acces_id' => $this->solicitud->id, // Relació directa amb la sol·licitud
            'nom' => "Assignar accés: {$sistema->nom}",
            'descripcio' => "Nivell d'accés: {$nivell->nom}\nEmpleat: {$empleat->nom_complet}\nDepartament: {$empleat->departament->nom}",
            'ordre' => 1,
            'obligatoria' => true,
            'completada' => false,
            'data_assignacio' => now(),
            'usuari_assignat_id' => null, // Assignació manual posterior
            'rol_assignat' => $rolGestor,
            'observacions' => "Sol·licitud: {$this->solicitud->identificador_unic}"
        ]);
        
        Log::info("Tasca per {$sistema->nom} assignada al rol {$rolGestor} (notificació automàtica)");
    }
    

    private function trobarUsuariIT(int $departamentId): ?User
    {
        // Prioritzar usuaris IT del mateix departament amb Shield
        $usuariIT = User::whereHas('roles', function($query) {
                $query->where('name', 'it');
            })
            ->where('actiu', true)
            ->whereHas('departamentsGestionats', function ($query) use ($departamentId) {
                $query->where('departament_id', $departamentId);
            })
            ->first();
            
        // Si no hi ha usuaris amb rol Shield, provar amb rol_principal
        if (!$usuariIT) {
            $usuariIT = User::where('rol_principal', 'it')
                ->where('actiu', true)
                ->whereHas('departamentsGestionats', function ($query) use ($departamentId) {
                    $query->where('departament_id', $departamentId);
                })
                ->first();
        }
        
        // Si no en troba, agafar qualsevol usuari IT amb Shield
        if (!$usuariIT) {
            $usuariIT = User::whereHas('roles', function($query) {
                    $query->where('name', 'it');
                })
                ->where('actiu', true)
                ->first();
        }
        
        // Fallback: si no hi ha usuaris amb rol Shield, provar amb rol_principal
        if (!$usuariIT) {
            $usuariIT = User::where('rol_principal', 'it')
                ->where('actiu', true)
                ->first();
        }
        
        return $usuariIT;
    }
}