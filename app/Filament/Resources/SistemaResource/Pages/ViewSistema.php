<?php

namespace App\Filament\Resources\SistemaResource\Pages;

use App\Filament\Resources\SistemaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\BadgeEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;

class ViewSistema extends ViewRecord
{
    protected static string $resource = SistemaResource::class;

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
                Section::make('Informació del Sistema')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('nom')
                                    ->label('Nom del Sistema'),
                                
                                IconEntry::make('actiu')
                                    ->boolean(),
                                
                                TextEntry::make('created_at')
                                    ->label('Data de creació')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                        
                        TextEntry::make('descripcio')
                            ->label('Descripció')
                            ->placeholder('Sense descripció')
                            ->columnSpanFull(),
                    ]),
                
                Section::make('Estadístiques')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('nivellsAcces_count')
                                    ->label('Nivells d\'Accés')
                                    ->badge()
                                    ->color('info'),
                                
                                TextEntry::make('sistemaValidadors_count')
                                    ->label('Validadors')
                                    ->badge()
                                    ->color('warning'),
                                
                                TextEntry::make('departaments_count')
                                    ->label('Departaments')
                                    ->badge()
                                    ->color('success'),
                                
                                TextEntry::make('solicituds_count')
                                    ->label('Sol·licituds')
                                    ->badge()
                                    ->color('gray')
                                    ->getStateUsing(fn () => 0), // Temporal
                            ]),
                    ]),
                
                Section::make('Validadors Configurats')
                    ->schema([
                        RepeatableEntry::make('sistemaValidadors')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        BadgeEntry::make('ordre')
                                            ->color('info'),
                                        
                                        BadgeEntry::make('tipus_validador')
                                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                                'usuari_especific' => 'Usuari',
                                                'gestor_departament' => 'Gestor Dept.',
                                                default => $state
                                            })
                                            ->colors([
                                                'primary' => 'usuari_especific',
                                                'success' => 'gestor_departament',
                                            ]),
                                        
                                        TextEntry::make('validador.name')
                                            ->label('Validador')
                                            ->placeholder('Gestor del departament'),
                                        
                                        IconEntry::make('requerit')
                                            ->boolean()
                                            ->label('Obligatori'),
                                    ]),
                            ])
                            ->columnSpanFull()
                    ])
                    ->visible(fn ($record) => $record->sistemaValidadors()->exists()),
            ]);
    }
}