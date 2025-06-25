<?php

namespace App\Filament\Resources\EmpleatResource\Pages;

use App\Filament\Resources\EmpleatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEmpleats extends ListRecords
{
    protected static string $resource = EmpleatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nou Empleat')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tots' => Tab::make('Tots')
                ->badge(fn () => $this->getModel()::count()),
                
            'actius' => Tab::make('Actius')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estat', 'actiu'))
                ->badge(fn () => $this->getModel()::where('estat', 'actiu')->count())
                ->badgeColor('success'),
                
            'baixa' => Tab::make('Baixa')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estat', 'baixa'))
                ->badge(fn () => $this->getModel()::where('estat', 'baixa')->count())
                ->badgeColor('danger'),
                
            'pendents_checklist' => Tab::make('Amb Checklists Pendents')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereHas('checklists', fn ($q) => $q->where('estat', '!=', 'completada'))
                )
                ->badge(fn () => $this->getModel()::whereHas('checklists', fn ($q) => $q->where('estat', '!=', 'completada'))->count())
                ->badgeColor('warning'),
        ];
    }
}