<?php

namespace App\Filament\Resources\ChecklistTemplateResource\Pages;

use App\Filament\Resources\ChecklistTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\BadgeEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;

class ViewChecklistTemplate extends ViewRecord
{
    protected static string $resource = ChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informació de la Plantilla')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('nom')
                                    ->label('Nom'),
                                
                                BadgeEntry::make('tipus')
                                    ->colors([
                                        'success' => 'onboarding',
                                        'warning' => 'offboarding',
                                    ]),
                                
                                IconEntry::make('actiu')
                                    ->boolean(),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('departament.nom')
                                    ->label('Departament')
                                    ->placeholder('Global (tots els departaments)'),
                                
                                TextEntry::make('created_at')
                                    ->label('Data de creació')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ]),
                
                Section::make('Tasques de la Plantilla')
                    ->schema([
                        RepeatableEntry::make('tasquesTemplate')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('ordre')
                                            ->badge()
                                            ->color('info'),
                                        
                                        TextEntry::make('nom')
                                            ->weight('medium')
                                            ->columnSpan(2),
                                        
                                        BadgeEntry::make('rol_assignat')
                                            ->colors([
                                                'danger' => 'it',
                                                'warning' => 'rrhh',
                                                'success' => 'gestor',
                                            ]),
                                    ]),
                                
                                TextEntry::make('descripcio')
                                    ->placeholder('Sense descripció')
                                    ->columnSpanFull(),
                                
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('dies_limit')
                                            ->label('Dies límit')
                                            ->placeholder('Sense límit')
                                            ->suffix(' dies'),
                                        
                                        IconEntry::make('obligatoria')
                                            ->boolean(),
                                        
                                        IconEntry::make('activa')
                                            ->boolean(),
                                    ]),
                            ])
                            ->columnSpanFull()
                    ]),
            ]);
    }
}