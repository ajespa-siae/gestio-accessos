<?php

namespace App\Filament\Operatiu\Resources\ProcessMobilitatGestorResource\Pages;

use App\Filament\Operatiu\Resources\ProcessMobilitatGestorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcessMobilitatGestors extends ListRecords
{
    protected static string $resource = ProcessMobilitatGestorResource::class;
    
    public function mount(): void
    {
        parent::mount();
        
        // Aplicar filtres per defecte per mostrar només processos pendents
        $this->tableFilters = [
            'estat' => [
                'values' => ['pendent_dept_actual', 'pendent_dept_nou']
            ]
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Els gestors no poden crear processos de mobilitat
        ];
    }
}
