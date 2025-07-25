<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SolicitudAcces;
use App\Models\User;
use App\Models\Validacio;
use App\Jobs\NotificarValidadorsPendents;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CrearValidacionsSolicitud implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SolicitudAcces $solicitud
    ) {}

    public function handle(): void
    {
        $startTime = microtime(true);
        Log::info("[INICI] Creant validacions per solicitud: {$this->solicitud->identificador_unic}");
        
        // Utilitzar transacció per assegurar consistència
        DB::transaction(function () use ($startTime) {
            $totalValidacions = 0;
            
            foreach ($this->solicitud->sistemesSolicitats as $sistemaSol) {
                $sistema = $sistemaSol->sistema;
                
                Log::info("Processant sistema: {$sistema->nom}");
                
                // Obtenir configuració de validadors del sistema
                $configuracioValidadors = $sistema->sistemaValidadors()
                    ->where('actiu', true)
                    ->orderBy('ordre')
                    ->get();
                
                if ($configuracioValidadors->isEmpty()) {
                    Log::warning("Sistema {$sistema->nom} no té validadors configurats");
                    continue;
                }
                
                // Processar cada configuració de validador
                foreach ($configuracioValidadors as $configValidador) {
                    if ($this->processarConfigValidador($sistema->id, $configValidador)) {
                        $totalValidacions++;
                    }
                }
            }
            
            // Verificar que s'han creat validacions
            if ($totalValidacions === 0) {
                Log::error("No s'han pogut crear validacions per la sol·licitud {$this->solicitud->identificador_unic}");
                throw new \Exception('No s\'han pogut crear validacions per la sol·licitud');
            }
            
            // Actualitzar estat de la sol·licitud
            $this->solicitud->update(['estat' => 'validant']);
            
            Log::info("Total validacions creades: {$totalValidacions} per solicitud: {$this->solicitud->identificador_unic}");
        });
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        Log::info("[FI] Validacions creades en {$duration}ms per solicitud: {$this->solicitud->identificador_unic}");
        
        // Notificar validadors (fora de la transacció)
        NotificarValidadorsPendents::dispatch($this->solicitud);
    }
    
    private function processarConfigValidador(int $sistemaId, $configValidador): bool
    {
        try {
            if ($configValidador->esUsuariEspecific()) {
                return $this->crearValidacioIndividual($sistemaId, $configValidador);
            } 
            
            if ($configValidador->esGestorDepartament()) {
                return $this->crearValidacioGrup($sistemaId, $configValidador);
            }
            
            Log::warning("Tipus de validador desconegut: {$configValidador->tipus_validador}");
            return false;
            
        } catch (\Exception $e) {
            Log::error("Error processant configuració validador {$configValidador->id}: {$e->getMessage()}");
            return false;
        }
    }
    
    private function crearValidacioIndividual(int $sistemaId, $configValidador): bool
    {
        $validador = $configValidador->validador;
        
        if (!$validador || !$validador->actiu) {
            Log::warning("Validador específic no trobat o inactiu per sistema {$sistemaId}");
            return false;
        }
        
        // Verificar que no existeixi ja
        $validacioExistent = $this->solicitud->validacions()
            ->where('sistema_id', $sistemaId)
            ->where('validador_id', $validador->id)
            ->where('tipus_validacio', 'individual')
            ->first();
            
        if ($validacioExistent) {
            Log::info("Validació individual ja existeix per {$validador->name} al sistema {$sistemaId}");
            return false;
        }
        
        $this->solicitud->validacions()->create([
            'sistema_id' => $sistemaId,
            'validador_id' => $validador->id,
            'estat' => 'pendent',
            'tipus_validacio' => 'individual',
            'config_validador_id' => $configValidador->id,
            'grup_validadors_ids' => null,
        ]);
        
        Log::info("Validació individual creada per {$validador->name} al sistema {$sistemaId}");
        return true;
    }
    
    private function crearValidacioGrup(int $sistemaId, $configValidador): bool
    {
        // Obtenir gestors actius del departament
        $gestors = $configValidador->getValidadorsPerSolicitud();
        
        if ($gestors->isEmpty()) {
            $departamentNom = $configValidador->getDepartamentValidadorNom() ?? 'Departament no configurat';
            Log::warning("Departament {$departamentNom} no té gestors actius");
            return false;
        }
        
        // Verificar que no existeixi ja una validació de grup per aquesta configuració
        $validacioGrupExistent = $this->solicitud->validacions()
            ->where('sistema_id', $sistemaId)
            ->where('tipus_validacio', 'grup')
            ->where('config_validador_id', $configValidador->id)
            ->first();
            
        if ($validacioGrupExistent) {
            Log::info("Validació de grup ja existeix per sistema {$sistemaId}, config {$configValidador->id}");
            return false;
        }
        
        // Determinar gestor representant (principal si hi ha, sinó el primer)
        $gestorRepresentant = $this->trobarGestorRepresentant($gestors, $configValidador);
        
        if (!$gestorRepresentant) {
            Log::warning("No s'ha pogut determinar un gestor representant per la validació de grup");
            return false;
        }
        
        // Crear validació de grup
        Log::info("Creant validació de grup per sistema {$sistemaId} amb gestor representant {$gestorRepresentant->name}");
        
        $validacio = $this->solicitud->validacions()->create([
            'sistema_id' => $sistemaId,
            'validador_id' => $gestorRepresentant->id, // Representant del grup
            'estat' => 'pendent',
            'tipus_validacio' => 'grup',
            'config_validador_id' => $configValidador->id,
            'grup_validadors_ids' => $gestors->pluck('id')->toArray(),
        ]);
        
        Log::info("Validació creada amb ID: {$validacio->id}");
        
        $departamentNom = $configValidador->getDepartamentValidadorNom();
        Log::info("Validació de grup creada per {$gestors->count()} gestors del departament {$departamentNom} al sistema {$sistemaId}");
        Log::info("Gestor representant: {$gestorRepresentant->name}");
        
        return true;
    }
    
    private function trobarGestorRepresentant($gestors, $configValidador)
    {
        // Si hi ha configuració de departament específica, buscar el gestor principal
        if ($configValidador->departamentValidador) {
            $gestorPrincipal = $configValidador->departamentValidador->gestors()
                ->wherePivot('gestor_principal', true)
                ->where('users.actiu', true)
                ->first();
                
            if ($gestorPrincipal && $gestors->contains('id', $gestorPrincipal->id)) {
                return $gestorPrincipal;
            }
        }
        
        // Si no hi ha principal o no està en la llista, retornar el primer gestor actiu
        return $gestors->where('actiu', true)->first();
    }
    
    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job CrearValidacionsSolicitud fallit per solicitud {$this->solicitud->identificador_unic}: {$exception->getMessage()}");
        
        // Marcar sol·licitud com amb error
        $this->solicitud->update([
            'estat' => 'error',
            'observacions' => 'Error creant validacions: ' . $exception->getMessage()
        ]);
    }
}