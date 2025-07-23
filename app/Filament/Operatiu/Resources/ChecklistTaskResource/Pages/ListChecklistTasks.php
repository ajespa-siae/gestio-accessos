<?php

namespace App\Filament\Operatiu\Resources\ChecklistTaskResource\Pages;

use App\Filament\Operatiu\Resources\ChecklistTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChecklistTasks extends ListRecords
{
    protected static string $resource = ChecklistTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    protected function getDefaultTableFiltersForm(): ?array
    {
        return [
            'completada' => [
                'value' => '0',
            ],
        ];
    }
    
    public function mount(): void
    {
        parent::mount();
        
        // Establir el filtre per defecte si no hi ha filtres aplicats
        if (empty($this->tableFilters)) {
            $this->tableFilters = [
                'completada' => [
                    'value' => '0',
                ],
            ];
        }
    }
}
