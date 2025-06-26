<?php

namespace App\Filament\Resources\SistemaResource\Pages;

use App\Filament\Resources\SistemaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditSistema extends EditRecord
{
    protected static string $resource = SistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Veure'),
                
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->visible(fn () => auth()->user()->rol_principal === 'admin'),
                
            // Action per activar/desactivar
            Actions\Action::make('toggle_actiu')
                ->label(fn () => $this->getRecord()->actiu ? 'Desactivar Sistema' : 'Activar Sistema')
                ->icon(fn () => $this->getRecord()->actiu ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->color(fn () => $this->getRecord()->actiu ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn () => 
                    ($this->getRecord()->actiu ? 'Desactivar' : 'Activar') . ' Sistema'
                )
                ->modalDescription(fn () => 
                    $this->getRecord()->actiu 
                        ? 'Aquest sistema ja no estarà disponible per sol·licituds d\'accés.'
                        : 'Aquest sistema tornarà a estar disponible per sol·licituds d\'accés.'
                )
                ->action(function () {
                    $sistema = $this->getRecord();
                    $sistema->update(['actiu' => !$sistema->actiu]);
                    
                    Notification::make()
                        ->title('Sistema ' . ($sistema->actiu ? 'activat' : 'desactivat'))
                        ->body("El sistema {$sistema->nom} ha estat " . 
                              ($sistema->actiu ? 'activat' : 'desactivat') . " correctament.")
                        ->success()
                        ->send();
                }),
                
            // Action per configuració ràpida
            Actions\Action::make('configuracio_rapida')
                ->label('Configuració Ràpida')
                ->icon('heroicon-o-bolt')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\Fieldset::make('Nivells d\'Accés Estàndard')
                        ->schema([
                            \Filament\Forms\Components\Checkbox::make('crear_consulta')
                                ->label('Nivell "Consulta"')
                                ->default(true),
                            \Filament\Forms\Components\Checkbox::make('crear_gestio')
                                ->label('Nivell "Gestió"')
                                ->default(true),
                            \Filament\Forms\Components\Checkbox::make('crear_administracio')
                                ->label('Nivell "Administració"')
                                ->default(false),
                        ]),
                        
                    \Filament\Forms\Components\Fieldset::make('Validadors Estàndard')
                        ->schema([
                            \Filament\Forms\Components\Select::make('validador_principal')
                                ->label('Validador Principal')
                                ->options(\App\Models\User::where('actiu', true)
                                    ->whereIn('rol_principal', ['admin', 'rrhh', 'it', 'gestor'])
                                    ->pluck('name', 'id'))
                                ->searchable(),
                        ]),
                ])
                ->action(function (array $data) {
                    $sistema = $this->getRecord();
                    $created = [];
                    
                    // Crear nivells estàndard
                    if ($data['crear_consulta'] ?? false) {
                        $sistema->nivellsAcces()->create([
                            'nom' => 'Consulta',
                            'descripcio' => 'Accés de només lectura',
                            'ordre' => 1,
                            'actiu' => true,
                        ]);
                        $created[] = 'Consulta';
                    }
                    
                    if ($data['crear_gestio'] ?? false) {
                        $sistema->nivellsAcces()->create([
                            'nom' => 'Gestió',
                            'descripcio' => 'Accés de lectura i escriptura',
                            'ordre' => 2,
                            'actiu' => true,
                        ]);
                        $created[] = 'Gestió';
                    }
                    
                    if ($data['crear_administracio'] ?? false) {
                        $sistema->nivellsAcces()->create([
                            'nom' => 'Administració',
                            'descripcio' => 'Accés complet al sistema',
                            'ordre' => 3,
                            'actiu' => true,
                        ]);
                        $created[] = 'Administració';
                    }
                    
                    // Afegir validador principal
                    if (!empty($data['validador_principal'])) {
                        $sistema->validadors()->syncWithoutDetaching([
                            $data['validador_principal'] => [
                                'ordre' => 1,
                                'requerit' => true,
                                'actiu' => true,
                            ]
                        ]);
                    }
                    
                    Notification::make()
                        ->title('Configuració ràpida aplicada')
                        ->body('S\'han creat els nivells: ' . implode(', ', $created))
                        ->success()
                        ->send();
                }),
        ];
    }
    
    protected function afterSave(): void
    {
        $sistema = $this->getRecord();
        
        // Verificar si el sistema té configuració completa
        $warnings = [];
        
        if ($sistema->nivellsAcces()->count() === 0) {
            $warnings[] = 'nivells d\'accés';
        }
        
        if ($sistema->validadors()->count() === 0) {
            $warnings[] = 'validadors';
        }
        
        if ($sistema->departaments()->count() === 0) {
            $warnings[] = 'departaments';
        }
        
        if (!empty($warnings) && $sistema->actiu) {
            Notification::make()
                ->title('Configuració incompleta')
                ->body('El sistema està actiu però li falten: ' . implode(', ', $warnings) . '.')
                ->warning()
                ->persistent()
                ->send();
        }
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Sistema actualitzat correctament';
    }
}