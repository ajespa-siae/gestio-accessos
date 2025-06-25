<?php

// app/Filament/Resources/SistemaResource/Pages/ViewSistema.php

namespace App\Filament\Resources\SistemaResource\Pages;

use App\Filament\Resources\SistemaResource;
use App\Models\Sistema;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BadgeEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;

class ViewSistema extends ViewRecord
{
    protected static string $resource = SistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('duplicar')
                ->label('Duplicar Sistema')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\TextInput::make('nou_nom')
                        ->label('Nom del nou sistema')
                        ->required()
                ])
                ->action(function (Sistema $record, array $data): void {
                    $nouSistema = $record->replicate();
                    $nouSistema->nom = $data['nou_nom'];
                    $nouSistema->save();
                    
                    // Duplicar nivells d'accés
                    foreach ($record->nivellsAcces as $nivell) {
                        $nouNivell = $nivell->replicate();
                        $nouNivell->sistema_id = $nouSistema->id;
                        $nouNivell->save();
                    }
                    
                    // Duplicar relacions amb departaments
                    $nouSistema->departaments()->sync($record->departaments->pluck('id'));
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Sistema duplicat')
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('configurar_nivells')
                ->label('Configurar Nivells')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('info')
                ->url(fn (Sistema $record): string => 
                    static::getResource()::getUrl('edit', ['record' => $record]) . '#nivells-acces'
                ),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Split::make([
                    Grid::make(2)
                        ->schema([
                            Section::make('Informació del Sistema')
                                ->schema([
                                    TextEntry::make('nom')
                                        ->label('Nom del Sistema'),
                                    BadgeEntry::make('actiu')
                                        ->label('Estat')
                                        ->getStateUsing(fn ($record) => $record->actiu ? 'Actiu' : 'Inactiu')
                                        ->colors([
                                            'success' => fn ($state) => $state === 'Actiu',
                                            'danger' => fn ($state) => $state === 'Inactiu',
                                        ]),
                                    TextEntry::make('descripcio')
                                        ->label('Descripció')
                                        ->placeholder('Sense descripció'),
                                ])
                                ->columnSpan(1),
                                
                            Section::make('Estadístiques')
                                ->schema([
                                    TextEntry::make('nivells_count')
                                        ->label('Nivells d\'Accés')
                                        ->getStateUsing(fn ($record) => $record->nivellsAcces()->count()),
                                    TextEntry::make('departaments_count')
                                        ->label('Departaments Autoritzats')
                                        ->getStateUsing(fn ($record) => $record->departaments()->count()),
                                    TextEntry::make('solicituds_total')
                                        ->label('Sol·licituds Total')
                                        ->getStateUsing(fn ($record) => $record->solicituds()->count()),
                                ])
                                ->columnSpan(1),
                        ]),
                        
                        Section::make('Configuració de Validadors')
                        ->schema([
                            RepeatableEntry::make('configuracio_validadors_decoded')
                                ->label('')
                                ->getStateUsing(function ($record) {
                                    // DECODIFICAR MANUALMENT EL JSON
                                    return $record->configuracio_validadors ? json_decode($record->configuracio_validadors, true) : [];
                                })
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextEntry::make('ordre')
                                                ->label('Ordre')
                                                ->badge()
                                                ->color('gray'),
                                            TextEntry::make('tipus')
                                                ->label('Tipus')
                                                ->formatStateUsing(function ($state, $record) {
                                                    return match($state) {
                                                        'usuari_especific' => 'Usuari Específic',
                                                        'rol' => 'Per Rol',
                                                        'gestor_departament' => 'Gestor del Departament',
                                                        'qualsevol_gestor' => 'Qualsevol Gestor',
                                                        default => $state
                                                    };
                                                }),
                                            TextEntry::make('detall')
                                                ->label('Detall')
                                                ->getStateUsing(function ($record) {
                                                    if (isset($record['usuari_id'])) {
                                                        $user = \App\Models\User::find($record['usuari_id']);
                                                        return $user ? $user->name : 'Usuari no trobat';
                                                    }
                                                    if (isset($record['rol'])) {
                                                        return ucfirst($record['rol']);
                                                    }
                                                    return '—';
                                                }),
                                        ]),
                                ])
                                ->placeholder('No hi ha validadors configurats'),
                        ])
                        ->collapsible(),
                        
                    Section::make('Departaments Autoritzats')
                        ->schema([
                            TextEntry::make('departaments.nom')
                                ->label('')
                                ->listWithLineBreaks()
                                ->placeholder('Tots els departaments (sense restriccions)'),
                        ])
                        ->collapsible(),
                ]),
            ]);
    }
}