<?php

namespace App\Filament\Resources\EmpleatResource\Pages;

use App\Filament\Resources\EmpleatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditEmpleat extends EditRecord
{
    protected static string $resource = EmpleatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Veure'),
                
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->visible(fn () => auth()->user()->rol_principal === 'admin'),
                
            // Action personalitzat per reactivar empleat
            Actions\Action::make('reactivar')
                ->label('Reactivar Empleat')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->visible(fn () => $this->getRecord()->estat === 'baixa')
                ->requiresConfirmation()
                ->modalHeading('Reactivar Empleat')
                ->modalDescription('Aquesta acció marcarà l\'empleat com a actiu novament.')
                ->action(function () {
                    $this->getRecord()->update([
                        'estat' => 'actiu',
                        'data_baixa' => null
                    ]);
                    
                    Notification::make()
                        ->title('Empleat reactivat')
                        ->body('L\'empleat ha estat marcat com a actiu.')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    protected function afterSave(): void
    {
        $empleat = $this->getRecord();
        
        // Si es canvia l'estat a baixa, crear offboarding
        if ($empleat->wasChanged('estat') && $empleat->estat === 'baixa') {
            try {
                \App\Jobs\CrearChecklistOffboarding::dispatch($empleat);
                
                Notification::make()
                    ->title('Procés d\'offboarding iniciat')
                    ->body('S\'ha creat automàticament la checklist d\'offboarding.')
                    ->info()
                    ->send();
                    
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Avís en offboarding')
                    ->body('Hi ha hagut un problema creant la checklist d\'offboarding: ' . $e->getMessage())
                    ->warning()
                    ->send();
            }
        }
        
        // Log dels canvis per auditoria
        if ($empleat->wasChanged()) {
            $changedFields = array_keys($empleat->getChanges());
            
            activity()
                ->performedOn($empleat)
                ->withProperties([
                    'camps_modificats' => $changedFields,
                    'valors_anteriors' => $empleat->getOriginal(),
                    'valors_nous' => $empleat->getChanges()
                ])
                ->log('Empleat modificat');
        }
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Empleat actualitzat correctament';
    }
}