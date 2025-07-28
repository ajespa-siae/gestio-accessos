<?php

namespace App\Filament\Resources\SistemaResource\Pages;

use App\Filament\Resources\SistemaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSistemes extends ListRecords
{
    protected static string $resource = SistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nou Sistema')
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'tots' => Tab::make('Tots')
                ->badge($this->getModel()::count()),
                
            'actius' => Tab::make('Actius')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('actiu', true))
                ->badge($this->getModel()::where('actiu', true)->count())
                ->badgeColor('success'),
                
            'inactius' => Tab::make('Inactius')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('actiu', false))
                ->badge($this->getModel()::where('actiu', false)->count())
                ->badgeColor('danger'),
                
            'sense_validadors' => Tab::make('Sense Validadors')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('actiu', true)->doesntHave('validadors')
                )
                ->badge($this->getModel()::where('actiu', true)
                    ->doesntHave('validadors')->count())
                ->badgeColor('warning'),
                
            'configuracio_incompleta' => Tab::make('Configuració Incompleta')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('actiu', true)
                          ->where(function ($q) {
                              $q->doesntHave('validadors')
                                ->orDoesntHave('nivellsAcces')
                                ->orDoesntHave('departaments');
                          })
                )
                ->badge($this->getModel()::where('actiu', true)
                    ->where(function ($q) {
                        $q->doesntHave('validadors')
                          ->orDoesntHave('nivellsAcces')
                          ->orDoesntHave('departaments');
                    })->count())
                ->badgeColor('warning'),
        ];
    }
}