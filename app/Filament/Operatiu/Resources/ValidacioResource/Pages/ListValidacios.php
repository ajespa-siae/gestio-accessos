<?php

namespace App\Filament\Operatiu\Resources\ValidacioResource\Pages;

use App\Filament\Operatiu\Resources\ValidacioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValidacios extends ListRecords
{
    protected static string $resource = ValidacioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Validació')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => auth()->user()->hasRole('admin')),
        ];
    }
    
    public function mount(): void
    {
        parent::mount();
        
        // Establir el filtre per defecte si no hi ha filtres aplicats
        if (empty($this->tableFilters)) {
            $this->tableFilters = [
                'estat' => [
                    'value' => 'pendent',
                ],
            ];
        }
    }
}
