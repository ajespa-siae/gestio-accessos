<?php
// app/Filament/Resources/SistemaResource/Pages/ListSistemes.php

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
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tots' => Tab::make('Tots')
                ->badge(fn () => $this->getModel()::count()),
                
            'actius' => Tab::make('Actius')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('actiu', true))
                ->badge(fn () => $this->getModel()::where('actiu', true)->count())
                ->badgeColor('success'),
                
            'inactius' => Tab::make('Inactius')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('actiu', false))
                ->badge(fn () => $this->getModel()::where('actiu', false)->count())
                ->badgeColor('danger'),
                
            'sense_nivells' => Tab::make('Sense Nivells')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereDoesntHave('nivellsAcces', fn ($q) => $q->where('actiu', true))
                )
                ->badge(fn () => $this->getModel()::whereDoesntHave('nivellsAcces', fn ($q) => $q->where('actiu', true))->count())
                ->badgeColor('warning'),
                
            // TAB SIMPLIFICAT PER EVITAR PROBLEMES POSTGRESQL  
            'sense_validadors' => Tab::make('Sense Validadors')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where(function ($q) {
                        $q->whereNull('configuracio_validadors')
                        ->orWhere('configuracio_validadors', '')
                        ->orWhere('configuracio_validadors', '[]')
                        ->orWhere('configuracio_validadors', '{}');
                    })
                )
                ->badge(fn () => $this->getModel()::where(function ($q) {
                    $q->whereNull('configuracio_validadors')
                    ->orWhere('configuracio_validadors', '')
                    ->orWhere('configuracio_validadors', '[]')
                    ->orWhere('configuracio_validadors', '{}');
                })->count())
                ->badgeColor('gray'),
        ];
    }
}