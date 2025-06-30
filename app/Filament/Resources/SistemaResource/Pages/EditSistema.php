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
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->before(function () {
                    // Verificar que no hi hagi sol·licituds pendents
                    // Temporal: comentat fins implementar SolicitudSistema
                    /*
                    $solicitudsPendents = \App\Models\SolicitudSistema::where('sistema_id', $this->getRecord()->id)
                        ->whereHas('solicitud', function ($q) {
                            $q->whereIn('estat', ['pendent', 'validant', 'aprovada']);
                        })
                        ->count();
                    
                    if ($solicitudsPendents > 0) {
                        Notification::make()
                            ->title('No es pot eliminar')
                            ->body("Aquest sistema té {$solicitudsPendents} sol·licituds pendents.")
                            ->danger()
                            ->send();
                        
                        $this->halt();
                    }
                    */
                }),
            
            Actions\Action::make('duplicar')
                ->label('Duplicar Sistema')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\TextInput::make('nou_nom')
                        ->label('Nom del Nou Sistema')
                        ->required()
                        ->default(fn () => $this->getRecord()->nom . ' (Còpia)'),
                    
                    \Filament\Forms\Components\Toggle::make('clonar_tot')
                        ->label('Clonar Configuració Completa')
                        ->default(true)
                        ->helperText('Inclou nivells, validadors i departaments'),
                ])
                ->action(function (array $data) {
                    $sistema = $this->getRecord();
                    
                    $nouSistema = $sistema->replicate();
                    $nouSistema->nom = $data['nou_nom'];
                    $nouSistema->actiu = false;
                    $nouSistema->save();
                    
                    if ($data['clonar_tot']) {
                        // Clonar nivells
                        foreach ($sistema->nivellsAcces as $nivell) {
                            $nouSistema->nivellsAcces()->create($nivell->toArray());
                        }
                        
                        // Clonar validadors
                        foreach ($sistema->sistemaValidadors as $validador) {
                            $nouSistema->sistemaValidadors()->create([
                                'validador_id' => $validador->validador_id,
                                'tipus_validador' => $validador->tipus_validador,
                                'ordre' => $validador->ordre,
                                'requerit' => $validador->requerit,
                                'actiu' => $validador->actiu,
                            ]);
                        }
                        
                        // Clonar departaments
                        foreach ($sistema->departaments as $departament) {
                            $nouSistema->departaments()->attach($departament->id, [
                                'acces_per_defecte' => $departament->pivot->acces_per_defecte
                            ]);
                        }
                    }
                    
                    Notification::make()
                        ->title('Sistema duplicat')
                        ->body("Sistema '{$data['nou_nom']}' creat correctament")
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('veure')
                                ->label('Editar Nou Sistema')
                                ->url(SistemaResource::getUrl('edit', ['record' => $nouSistema]))
                        ])
                        ->persistent()
                        ->send();
                }),
            
            Actions\Action::make('estadistiques')
                ->label('Estadístiques')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalContent(function () {
                    $sistema = $this->getRecord();
                    
                    // Estadístiques del sistema
                    $stats = [
                        'Nivells d\'accés' => $sistema->nivellsAcces()->count(),
                        'Validadors configurats' => $sistema->sistemaValidadors()->count(),
                        'Validadors específics' => $sistema->sistemaValidadors()->where('tipus_validador', 'usuari_especific')->count(),
                        'Validadors gestor dept.' => $sistema->sistemaValidadors()->where('tipus_validador', 'gestor_departament')->count(),
                        'Departaments assignats' => $sistema->departaments()->count(),
                        'Sol·licituds totals' => 0, // Temporal
                        'Sol·licituds pendents' => 0, // Temporal
                    ];
                    
                    $html = '<div class="space-y-4">';
                    foreach ($stats as $label => $value) {
                        $html .= "<div class='flex justify-between'>";
                        $html .= "<span class='font-medium'>{$label}:</span>";
                        $html .= "<span class='text-primary-600'>{$value}</span>";
                        $html .= "</div>";
                    }
                    $html .= '</div>';
                    
                    return new \Illuminate\Support\HtmlString($html);
                })
                ->modalHeading('Estadístiques del Sistema')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tancar'),
        ];
    }
    
    protected function afterSave(): void
    {
        Notification::make()
            ->title('Sistema actualitzat')
            ->body("El sistema '{$this->getRecord()->nom}' s'ha actualitzat correctament.")
            ->success()
            ->send();
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Sistema actualitzat correctament';
    }
}