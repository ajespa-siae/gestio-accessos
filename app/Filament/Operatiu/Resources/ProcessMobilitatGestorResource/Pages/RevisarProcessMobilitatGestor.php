<?php

namespace App\Filament\Operatiu\Resources\ProcessMobilitatGestorResource\Pages;

use App\Filament\Operatiu\Resources\ProcessMobilitatGestorResource;
use App\Models\ProcessMobilitat;
use App\Models\ProcessMobilitatSistema;
use App\Models\Sistema;
use App\Models\NivellAccesSistema;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action as HeaderAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RevisarProcessMobilitatGestor extends Page
{
    protected static string $resource = ProcessMobilitatGestorResource::class;
    
    protected static string $view = 'filament.operatiu.resources.process-mobilitat-gestor-resource.pages.revisar-process-mobilitat-gestor';
    
    public ProcessMobilitat $record;
    
    public function mount(ProcessMobilitat $record): void
    {
        $this->record = $record;
        
        // Verificar que el gestor té permisos per revisar aquest procés
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            $departaments = $user->departamentsGestionats->pluck('id');
            
            // Verificar permisos bàsics
            if (!$departaments->contains($record->departament_actual_id) && 
                !$departaments->contains($record->departament_nou_id)) {
                abort(403, 'No tens permisos per revisar aquest procés de mobilitat.');
            }
            
            // Verificar estat i departament corresponent
            if ($record->estat === 'pendent_dept_actual') {
                // Només gestors del departament actual poden revisar
                if (!$departaments->contains($record->departament_actual_id)) {
                    abort(403, 'Aquest procés està pendent de revisió del departament actual.');
                }
            } elseif ($record->estat === 'pendent_dept_nou') {
                // Només gestors del departament nou poden revisar
                if (!$departaments->contains($record->departament_nou_id)) {
                    abort(403, 'Aquest procés està pendent de revisió del departament nou.');
                }
            } else {
                // Si l'estat no és pendent, no es pot revisar
                abort(403, 'Aquest procés ja ha estat processat.');
            }
        }
    }
    
    public function getTitle(): string
    {
        return "Revisar Procés de Mobilitat - {$this->record->identificador_unic}";
    }
    
    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('processar')
                ->label('Processar')
                ->color('success')
                ->icon('heroicon-o-check')
                ->action('processar')
                ->visible(fn (): bool => 
                    $this->record->estat === 'pendent_dept_actual' || 
                    $this->record->estat === 'pendent_dept_nou'
                ),
        ];
    }
    
    public function processar(): void
    {
        $user = Auth::user();
        $departaments = $user->departamentsGestionats->pluck('id');
        
        // Verificar que l'usuari pot processar aquest procés
        if (!$user->hasRole('admin')) {
            if ($this->record->estat === 'pendent_dept_actual' && 
                !$departaments->contains($this->record->departament_actual_id)) {
                Notification::make()
                    ->title('Error')
                    ->body('No tens permisos per processar aquest procés.')
                    ->danger()
                    ->send();
                return;
            }
            
            if ($this->record->estat === 'pendent_dept_nou' && 
                !$departaments->contains($this->record->departament_nou_id)) {
                Notification::make()
                    ->title('Error')
                    ->body('No tens permisos per processar aquest procés.')
                    ->danger()
                    ->send();
                return;
            }
            
            if (!in_array($this->record->estat, ['pendent_dept_actual', 'pendent_dept_nou'])) {
                Notification::make()
                    ->title('Error')
                    ->body('Aquest procés ja ha estat processat.')
                    ->danger()
                    ->send();
                return;
            }
        }
        
        try {
            if ($this->record->estat === 'pendent_dept_actual' && 
                $departaments->contains($this->record->departament_actual_id)) {
                
                // Marcar com processat pel departament actual
                $this->record->sistemes()->update(['processat_dept_actual' => true]);
                
                // Canviar estat i notificar departament nou
                $this->record->update(['estat' => 'pendent_dept_nou']);
                dispatch(new \App\Jobs\NotificarGestorsDepartamentNou($this->record));
                
                Notification::make()
                    ->title('Procés enviat')
                    ->body('El procés s\'ha enviat al departament nou per revisió.')
                    ->success()
                    ->send();
                    
                // Redirigir a la llista de processos
                $this->redirect(ProcessMobilitatGestorResource::getUrl('index'));
                return;
                
            } elseif ($this->record->estat === 'pendent_dept_nou' && 
                      $departaments->contains($this->record->departament_nou_id)) {
            
            // Marcar com processat pel departament nou
            $this->record->sistemes()->update(['processat_dept_nou' => true]);
            
            // Crear sol·licitud d'accés de forma síncrona
            $this->crearSolicitudAccessMobilitat();
            
            Notification::make()
                ->title('Procés enviat a validació')
                ->body('S\'ha creat la sol·licitud d\'accés i s\'ha enviat als validadors corresponents.')
                ->success()
                ->send();
                
            // Redirigir a la llista de processos
            $this->redirect(ProcessMobilitatGestorResource::getUrl('index'));
            return;
        }
            
            Log::info("Procés de mobilitat processat per gestor: {$this->record->identificador_unic}");
            
        } catch (\Exception $e) {
            Log::error("Error processant mobilitat: {$e->getMessage()}");
            
            // Missatge d'error més específic
            $missatge = 'Error processant el procés de mobilitat';
            if (str_contains($e->getMessage(), 'sistemes associats')) {
                $missatge = 'No es pot processar: l\'empleat no té sistemes d\'accés actuals';
            }
            
            Notification::make()
                ->title($missatge)
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function getSistemesData(): array
    {
        return $this->record->sistemes()
            ->with(['sistema', 'nivellAccesOriginal'])
            ->get()
            ->toArray();
    }
    
    public function getSistemesDisponibles()
    {
        return \App\Models\Sistema::with('nivellsAcces')->orderBy('nom')->get();
    }
    
    public function getNivellsAcces()
    {
        return \App\Models\NivellAccesSistema::orderBy('nom')->get();
    }
    
    public function getSistemesNivellsJson()
    {
        $sistemes = \App\Models\Sistema::with('nivellsAcces')->orderBy('nom')->get();
        $data = [];
        
        foreach ($sistemes as $sistema) {
            $data[$sistema->id] = $sistema->nivellsAcces->map(function($nivell) {
                return [
                    'id' => $nivell->id,
                    'nom' => $nivell->nom
                ];
            })->toArray();
        }
        
        return json_encode($data);
    }
    
    private function crearSolicitudAccessMobilitat(): void
    {
        try {
            // Verificar que hi ha sistemes per processar
            if ($this->record->sistemes->isEmpty()) {
                throw new \Exception('No hi ha sistemes associats a aquest procés de mobilitat. L\'empleat potser no té sistemes actuals assignats.');
            }
            
            // Crear sol·licitud d'accés
            $solicitud = \App\Models\SolicitudAcces::create([
                'identificador_unic' => \App\Models\SolicitudAcces::generarIdentificador(),
                'empleat_destinatari_id' => $this->record->empleat_id,
                'usuari_solicitant_id' => $this->record->usuari_solicitant_id,
                'estat' => 'pendent',
                'tipus' => 'mobilitat',
                'data_inici_necessaria' => now()->addDays(1),
                'justificacio' => "Mobilitat: {$this->record->justificacio}",
                'process_mobilitat_id' => $this->record->id
            ]);

            $sistemesSolicitats = 0;
            
            // Crear sistemes sol·licitats basats en les decisions dels departaments
            foreach ($this->record->sistemes as $sistemaMobilitat) {
                // Calcular estat final
                $estatFinal = $sistemaMobilitat->calcularEstatFinal();
                $sistemaMobilitat->update(['estat_final' => $estatFinal]);

                // Només crear sol·licituds per sistemes que s'afegeixen o modifiquen
                if (in_array($estatFinal, ['afegir', 'modificar'])) {
                    \App\Models\SolicitudSistema::create([
                        'solicitud_acces_id' => $solicitud->id,
                        'sistema_id' => $sistemaMobilitat->sistema_id,
                        'nivell_acces_id' => $sistemaMobilitat->nivell_acces_final_id,
                        'aprovat' => false // Necessita validació
                    ]);
                    $sistemesSolicitats++;
                }
            }
            
            // Verificar que s'han creat sistemes per sol·licitar
            if ($sistemesSolicitats === 0) {
                // Si no hi ha sistemes nous o modificats, marcar com finalitzat directament
                $this->record->update([
                    'solicitud_acces_id' => $solicitud->id,
                    'estat' => 'finalitzada',
                    'data_finalitzacio' => now()
                ]);
                
                $solicitud->update(['estat' => 'finalitzada']);
                
                \Illuminate\Support\Facades\Log::info("Mobilitat finalitzada sense canvis: {$this->record->identificador_unic}");
            } else {
                // Actualitzar el procés de mobilitat immediatament
                $this->record->update([
                    'solicitud_acces_id' => $solicitud->id,
                    'estat' => 'validant'
                ]);

                \Illuminate\Support\Facades\Log::info("Sol·licitud d'accés creada per mobilitat: {$solicitud->identificador_unic}");

                // Disparar validacions de forma asíncrona (només les notificacions)
                dispatch(new \App\Jobs\CrearValidacionsSolicitud($solicitud));
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error creant sol·licitud d'accés per mobilitat: {$e->getMessage()}");
            throw $e;
        }
    }
}
