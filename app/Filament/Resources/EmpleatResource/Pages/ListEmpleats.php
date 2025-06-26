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
                ->icon('heroicon-o-plus')
                ->color('success'),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'tots' => Tab::make('Tots')
                ->badge($this->getModel()::count()),
                
            'actius' => Tab::make('Actius')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estat', 'actiu'))
                ->badge($this->getModel()::where('estat', 'actiu')->count())
                ->badgeColor('success'),
                
            'baixa' => Tab::make('Baixa')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estat', 'baixa'))
                ->badge($this->getModel()::where('estat', 'baixa')->count())
                ->badgeColor('danger'),
                
            'onboarding_pendent' => Tab::make('Onboarding Pendent')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('estat', 'actiu')
                          ->whereDoesntHave('checklists', function ($q) {
                              $q->whereHas('template', function ($t) {
                                  $t->where('tipus', 'onboarding');
                              });
                          })
                )
                ->badge($this->getModel()::where('estat', 'actiu')
                    ->whereDoesntHave('checklists', function ($q) {
                        $q->whereHas('template', function ($t) {
                            $t->where('tipus', 'onboarding');
                        });
                    })->count())
                ->badgeColor('warning'),
                
            'sense_solicituds' => Tab::make('Sense Sol·licituds')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('estat', 'actiu')->doesntHave('solicitudsAcces')
                )
                ->badge($this->getModel()::where('estat', 'actiu')
                    ->doesntHave('solicitudsAcces')->count())
                ->badgeColor('info'),
        ];
    }
}
