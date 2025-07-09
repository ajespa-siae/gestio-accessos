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

class CrearValidacionsSolicitudDebug implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SolicitudAcces $solicitud
    ) {}

    public function handle(): void
    {
        Log::info("DEBUG - Creant validacions per solicitud: {$this->solicitud->identificador_unic}");
        
        // Utilitzar transacció per assegurar consistència
        DB::transaction(function () {
            $totalValidacions = 0;
            
            foreach ($this->solicitud->sistemesSolicitats as $sistemaSol) {
                $sistema = $sistemaSol->sistema;
                
                Log::info("DEBUG - Processant sistema: {$sistema->nom} (ID: {$sistema->id})");
                
                // Obtenir configuració de validadors del sistema
                $configuracioValidadors = $sistema->sistemaValidadors()
                    ->where('actiu', true)
                    ->orderBy('ordre')
                    ->get();
                
                Log::info("DEBUG - Trobats " . $configuracioValidadors->count() . " validadors configurats per al sistema");
                
                if ($configuracioValidadors->isEmpty()) {
                    Log::warning("DEBUG - Sistema {$sistema->nom} no té validadors configurats");
                    continue;
                }
                
                // Processar cada configuració de validador
                foreach ($configuracioValidadors as $configValidador) {
                    Log::info("DEBUG - Processant config validador ID: {$configValidador->id}, tipus: {$configValidador->tipus_validador}");
                    
                    try {
                        if ($configValidador->tipus_validador === 'usuari_especific') {
                            Log::info("DEBUG - Es un validador de tipus usuari_especific");
                            $validador = $configValidador->validador;
                            Log::info("DEBUG - Validador: " . ($validador ? $validador->name : 'No trobat'));
                            
                            if (!$validador || !$validador->actiu) {
                                Log::warning("DEBUG - Validador específic no trobat o inactiu per sistema {$sistema->id}");
                                continue;
                            }
                            
                            // Verificar que no existeixi ja
                            $validacioExistent = $this->solicitud->validacions()
                                ->where('sistema_id', $sistema->id)
                                ->where('validador_id', $validador->id)
                                ->where('tipus_validacio', 'individual')
                                ->first();
                                
                            if ($validacioExistent) {
                                Log::info("DEBUG - Validació individual ja existeix per {$validador->name} al sistema {$sistema->id}");
                                continue;
                            }
                            
                            $this->solicitud->validacions()->create([
                                'sistema_id' => $sistema->id,
                                'validador_id' => $validador->id,
                                'estat' => 'pendent',
                                'tipus_validacio' => 'individual',
                                'config_validador_id' => $configValidador->id,
                                'grup_validadors_ids' => null,
                            ]);
                            
                            Log::info("DEBUG - Validació individual creada per {$validador->name} al sistema {$sistema->id}");
                            $totalValidacions++;
                        } 
                        else if ($configValidador->tipus_validador === 'gestor_departament') {
                            Log::info("DEBUG - Es un validador de tipus gestor_departament");
                            
                            // Obtenir gestors actius del departament
                            $gestors = $configValidador->getValidadorsPerSolicitud();
                            Log::info("DEBUG - Trobats " . $gestors->count() . " gestors per al departament");
                            
                            if ($gestors->isEmpty()) {
                                $departamentId = $configValidador->departament_validador_id;
                                $departamentNom = DB::table('departaments')->where('id', $departamentId)->value('nom') ?? 'Departament no configurat';
                                Log::warning("DEBUG - Departament {$departamentNom} (ID: {$departamentId}) no té gestors actius");
                                continue;
                            }
                            
                            // Verificar que no existeixi ja una validació de grup per aquesta configuració
                            $validacioGrupExistent = $this->solicitud->validacions()
                                ->where('sistema_id', $sistema->id)
                                ->where('tipus_validacio', 'grup')
                                ->where('config_validador_id', $configValidador->id)
                                ->first();
                                
                            if ($validacioGrupExistent) {
                                Log::info("DEBUG - Validació de grup ja existeix per sistema {$sistema->id}, config {$configValidador->id}");
                                continue;
                            }
                            
                            // Determinar gestor representant (principal si hi ha, sinó el primer)
                            $gestorRepresentant = null;
                            
                            // Si hi ha configuració de departament específica, buscar el gestor principal
                            if ($configValidador->departament_validador_id) {
                                $departamentId = $configValidador->departament_validador_id;
                                Log::info("DEBUG - Buscant gestor principal per departament ID: {$departamentId}");
                                
                                $gestorPrincipal = DB::table('departament_user')
                                    ->where('departament_id', $departamentId)
                                    ->where('gestor_principal', true)
                                    ->join('users', 'users.id', '=', 'departament_user.user_id')
                                    ->where('users.actiu', true)
                                    ->select('users.*')
                                    ->first();
                                    
                                if ($gestorPrincipal) {
                                    Log::info("DEBUG - Trobat gestor principal: {$gestorPrincipal->name}");
                                    $gestorRepresentant = User::find($gestorPrincipal->id);
                                }
                            }
                            
                            // Si no hi ha principal o no està en la llista, retornar el primer gestor actiu
                            if (!$gestorRepresentant) {
                                $gestorRepresentant = $gestors->where('actiu', true)->first();
                                Log::info("DEBUG - Utilitzant primer gestor actiu: " . ($gestorRepresentant ? $gestorRepresentant->name : 'No trobat'));
                            }
                            
                            if (!$gestorRepresentant) {
                                Log::warning("DEBUG - No s'ha pogut determinar un gestor representant per la validació de grup");
                                continue;
                            }
                            
                            // Crear validació de grup
                            $this->solicitud->validacions()->create([
                                'sistema_id' => $sistema->id,
                                'validador_id' => $gestorRepresentant->id, // Representant del grup
                                'estat' => 'pendent',
                                'tipus_validacio' => 'grup',
                                'config_validador_id' => $configValidador->id,
                                'grup_validadors_ids' => json_encode($gestors->pluck('id')->toArray()),
                            ]);
                            
                            $departamentNom = DB::table('departaments')->where('id', $configValidador->departament_validador_id)->value('nom') ?? 'Departament desconegut';
                            Log::info("DEBUG - Validació de grup creada per {$gestors->count()} gestors del departament {$departamentNom} al sistema {$sistema->id}");
                            Log::info("DEBUG - Gestor representant: {$gestorRepresentant->name}");
                            
                            $totalValidacions++;
                        }
                        else {
                            Log::warning("DEBUG - Tipus de validador desconegut: {$configValidador->tipus_validador}");
                        }
                    } catch (\Exception $e) {
                        Log::error("DEBUG - Error processant configuració validador {$configValidador->id}: {$e->getMessage()}");
                        Log::error("DEBUG - Stack trace: " . $e->getTraceAsString());
                    }
                }
            }
            
            // Verificar que s'han creat validacions
            if ($totalValidacions === 0) {
                Log::error("DEBUG - No s'han pogut crear validacions per la sol·licitud {$this->solicitud->identificador_unic}");
                throw new \Exception('No s\'han pogut crear validacions per la sol·licitud');
            }
            
            // Actualitzar estat de la sol·licitud
            $this->solicitud->update(['estat' => 'validant']);
            
            Log::info("DEBUG - Total validacions creades: {$totalValidacions} per solicitud: {$this->solicitud->identificador_unic}");
        });
        
        // Notificar validadors (fora de la transacció)
        NotificarValidadorsPendents::dispatch($this->solicitud);
    }
    
    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("DEBUG - Job CrearValidacionsSolicitudDebug fallit per solicitud {$this->solicitud->identificador_unic}: {$exception->getMessage()}");
        Log::error("DEBUG - Stack trace: " . $exception->getTraceAsString());
    }
}
