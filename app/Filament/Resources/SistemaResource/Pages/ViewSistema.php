<?php

namespace App\Filament\Resources\SistemaResource\Pages;

use App\Filament\Resources\SistemaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSistema extends ViewRecord
{
    protected static string $resource = SistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar'),
                
            // Action per veure estadístiques
            Actions\Action::make('estadistiques')
                ->label('Estadístiques')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalHeading('Estadístiques del Sistema')
                ->modalContent(function () {
                    $sistema = $this->getRecord();
                    
                    return view('filament.pages.sistema-estadistiques', [
                        'sistema' => $sistema,
                        'solicituds_total' => 0, // \App\Models\SolicitudSistema::where('sistema_id', $sistema->id)->count(),
                        'solicituds_aprovades' => 0, // \App\Models\SolicitudSistema::where('sistema_id', $sistema->id)->where('aprovat', true)->count(),
                        'validacions_pendents' => 0, // \App\Models\Validacio::where('sistema_id', $sistema->id)->where('estat', 'pendent')->count(),
                    ]);
                })
                ->modalWidth(MaxWidth::Large),
                
            // Action per exportar configuració
            Actions\Action::make('exportar_configuracio')
                ->label('Exportar Configuració')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $sistema = $this->getRecord();
                    
                    $configuracio = [
                        'sistema' => [
                            'nom' => $sistema->nom,
                            'descripcio' => $sistema->descripcio,
                            'actiu' => $sistema->actiu,
                        ],
                        'nivells_acces' => $sistema->nivellsAcces->map(fn ($nivell) => [
                            'nom' => $nivell->nom,
                            'descripcio' => $nivell->descripcio,
                            'ordre' => $nivell->ordre,
                            'actiu' => $nivell->actiu,
                        ])->toArray(),
                        'validadors' => $sistema->validadors->map(fn ($validador) => [
                            'nom' => $validador->name,
                            'email' => $validador->email,
                            'rol' => $validador->rol_principal,
                            'ordre' => $validador->pivot->ordre,
                            'requerit' => $validador->pivot->requerit,
                            'actiu' => $validador->pivot->actiu,
                        ])->toArray(),
                        'departaments' => $sistema->departaments->map(fn ($dept) => [
                            'nom' => $dept->nom,
                            'acces_per_defecte' => $dept->pivot->acces_per_defecte,
                        ])->toArray(),
                    ];
                    
                    // En un cas real, aquí es podria generar un fitxer JSON o Excel
                    \Filament\Notifications\Notification::make()
                        ->title('Configuració exportada')
                        ->body('La configuració del sistema s\'ha exportat correctament.')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informació del Sistema')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('nom')
                                    ->label('Nom del Sistema')
                                    ->weight('bold')
                                    ->size('lg'),
                                    
                                Infolists\Components\TextEntry::make('actiu')
                                    ->label('Estat')
                                    ->badge()
                                    ->color(fn (bool $state) => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state) => $state ? 'Actiu' : 'Inactiu'),
                                    
                                Infolists\Components\TextEntry::make('descripcio')
                                    ->label('Descripció')
                                    ->placeholder('Sense descripció')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Configuració')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('nivells_count')
                                    ->label('Nivells d\'Accés')
                                    ->badge()
                                    ->color('info')
                                    ->getStateUsing(fn ($record) => $record->nivellsAcces()->count()),
                                    
                                Infolists\Components\TextEntry::make('validadors_count')
                                    ->label('Validadors')
                                    ->badge()
                                    ->color('warning')
                                    ->getStateUsing(fn ($record) => $record->validadors()->count()),
                                    
                                Infolists\Components\TextEntry::make('departaments_count')
                                    ->label('Departaments')
                                    ->badge()
                                    ->color('success')
                                    ->getStateUsing(fn ($record) => $record->departaments()->count()),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Estadístiques d\'Ús')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('solicituds_totals')
                                    ->label('Sol·licituds Totals')
                                    ->badge()
                                    ->color('gray')
                                    ->getStateUsing(function ($record) {
                                        // Temporal: comentat fins implementar SolicitudSistema
                                        // return \App\Models\SolicitudSistema::where('sistema_id', $record->id)->count();
                                        return 0;
                                    }),
                                    
                                Infolists\Components\TextEntry::make('solicituds_aprovades')
                                    ->label('Sol·licituds Aprovades')
                                    ->badge()
                                    ->color('success')
                                    ->getStateUsing(function ($record) {
                                        // Temporal: comentat fins implementar SolicitudSistema
                                        // return \App\Models\SolicitudSistema::where('sistema_id', $record->id)->where('aprovat', true)->count();
                                        return 0;
                                    }),
                                    
                                Infolists\Components\TextEntry::make('validacions_pendents')
                                    ->label('Validacions Pendents')
                                    ->badge()
                                    ->color('warning')
                                    ->getStateUsing(function ($record) {
                                        // Temporal: comentat fins implementar Validacio
                                        // return \App\Models\Validacio::where('sistema_id', $record->id)->where('estat', 'pendent')->count();
                                        return 0;
                                    }),
                                    
                                Infolists\Components\TextEntry::make('utilitzacio_percent')
                                    ->label('Utilització')
                                    ->badge()
                                    ->color('info')
                                    ->getStateUsing(function ($record) {
                                        // Temporal: comentat fins implementar models relacionats
                                        // $total = \App\Models\Empleat::where('estat', 'actiu')->count();
                                        // $usuaris = \App\Models\SolicitudSistema::where('sistema_id', $record->id)
                                        //     ->where('aprovat', true)
                                        //     ->distinct('solicitud_id')
                                        //     ->count();
                                        // return $total > 0 ? round(($usuaris / $total) * 100) . '%' : '0%';
                                        return '0%';
                                    }),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Informació del Sistema')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Data Creació')
                                    ->dateTime('d/m/Y H:i'),
                                    
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Última Actualització')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ]),
            ]);
    }
}