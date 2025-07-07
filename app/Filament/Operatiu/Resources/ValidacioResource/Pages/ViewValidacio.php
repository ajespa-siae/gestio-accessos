<?php

namespace App\Filament\Operatiu\Resources\ValidacioResource\Pages;

use App\Filament\Operatiu\Resources\ValidacioResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewValidacio extends ViewRecord
{
    protected static string $resource = ValidacioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->visible(fn (): bool => 
                    $this->record->estat === 'pendent' && 
                    auth()->user()->hasRole('admin')
                ),
                
            Actions\Action::make('aprovar')
                ->label('Aprovar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aprovar validació')
                ->modalDescription('Estàs segur que vols aprovar aquesta validació?')
                ->form([
                    \Filament\Forms\Components\Textarea::make('observacions')
                        ->label('Observacions (opcionals)')
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    $this->record->aprovar(
                        auth()->user(),
                        $data['observacions'] ?? null
                    );
                    $this->refreshFormData(['estat', 'data_validacio', 'observacions']);
                })
                ->visible(fn (): bool => 
                    $this->record->estat === 'pendent' && 
                    $this->record->potValidar(auth()->user())
                ),
                
            Actions\Action::make('rebutjar')
                ->label('Rebutjar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Rebutjar validació')
                ->modalDescription('Estàs segur que vols rebutjar aquesta validació?')
                ->form([
                    \Filament\Forms\Components\Textarea::make('observacions')
                        ->label('Raó del rebuig')
                        ->required()
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    $this->record->rebutjar(
                        auth()->user(),
                        $data['observacions']
                    );
                    $this->refreshFormData(['estat', 'data_validacio', 'observacions']);
                })
                ->visible(fn (): bool => 
                    $this->record->estat === 'pendent' && 
                    $this->record->potValidar(auth()->user())
                ),
        ];
    }
}
