<?php

namespace App\Filament\Operatiu\Resources\ProcessMobilitatResource\Pages;

use App\Filament\Operatiu\Resources\ProcessMobilitatResource;
use App\Models\ProcessMobilitat;
use App\Models\Empleat;
use App\Models\ProcessMobilitatSistema;
use App\Models\SolicitudSistema;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateProcessMobilitat extends CreateRecord
{
    protected static string $resource = ProcessMobilitatResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generar identificador únic
        $data['identificador_unic'] = ProcessMobilitat::generarIdentificador();
        
        // Assignar usuari sol·licitant
        $data['usuari_solicitant_id'] = Auth::id();
        
        // Estat inicial
        $data['estat'] = 'pendent_dept_actual';
        
        // Assegurar-se que departament_actual_id està assignat
        if (empty($data['departament_actual_id']) && !empty($data['empleat_id'])) {
            $empleat = \App\Models\Empleat::find($data['empleat_id']);
            if ($empleat && $empleat->departament_id) {
                $data['departament_actual_id'] = $empleat->departament_id;
            }
        }
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        $processMobilitat = $this->record;
        
        try {
            // Obtenir sistemes actuals de l'empleat
            $empleat = $processMobilitat->empleat;
            $sistemesActuals = $this->obtenirSistemesEmpleat($empleat);
            
            // Crear registres per cada sistema actual
            foreach ($sistemesActuals as $sistema) {
                ProcessMobilitatSistema::create([
                    'process_mobilitat_id' => $processMobilitat->id,
                    'sistema_id' => $sistema['sistema_id'],
                    'nivell_acces_original_id' => $sistema['nivell_acces_id'],
                    'accio_dept_actual' => 'mantenir',
                    'accio_dept_nou' => 'mantenir',
                    'estat_final' => 'mantenir',
                    'processat_dept_actual' => false,
                    'processat_dept_nou' => false
                ]);
            }
            
            // Notificar gestors del departament actual
            dispatch(new \App\Jobs\NotificarGestorsDepartamentActual($processMobilitat));
            
            Log::info("Procés de mobilitat creat: {$processMobilitat->identificador_unic}");
            
        } catch (\Exception $e) {
            Log::error("Error creant procés de mobilitat: {$e->getMessage()}");
        }
    }
    
    private function obtenirSistemesEmpleat(Empleat $empleat): array
    {
        // Buscar sistemes actuals de l'empleat a través de sol·licituds aprovades
        $sistemes = [];
        
        $solicitudsAprovades = $empleat->solicitudsAcces()
            ->where('estat', 'finalitzada')
            ->with('sistemesSolicitats.sistema', 'sistemesSolicitats.nivellAcces')
            ->get();
            
        foreach ($solicitudsAprovades as $solicitud) {
            foreach ($solicitud->sistemesSolicitats as $sistemaSolicitat) {
                if ($sistemaSolicitat->aprovat) {
                    $sistemes[] = [
                        'sistema_id' => $sistemaSolicitat->sistema_id,
                        'nivell_acces_id' => $sistemaSolicitat->nivell_acces_id
                    ];
                }
            }
        }
        
        // Eliminar duplicats
        return collect($sistemes)
            ->unique(function ($item) {
                return $item['sistema_id'] . '-' . $item['nivell_acces_id'];
            })
            ->values()
            ->toArray();
    }
}
