<?php

namespace App\Filament\Resources\EmpleatResource\Pages;

use App\Filament\Resources\EmpleatResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewEmpleat extends ViewRecord
{
    protected static string $resource = EmpleatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar'),
                
            // Action per baixa ràpida
            Actions\Action::make('baixa_rapida')
                ->label('Donar de Baixa')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn () => $this->getRecord()->estat === 'actiu')
                ->form([
                    \Filament\Forms\Components\Textarea::make('observacions_baixa')
                        ->label('Motiu de la baixa')
                        ->required()
                        ->rows(3),
                    \Filament\Forms\Components\DateTimePicker::make('data_baixa_efectiva')
                        ->label('Data efectiva')
                        ->default(now())
                        ->required(),
                ])
                ->modalWidth(MaxWidth::Medium)
                ->action(function (array $data) {
                    $this->getRecord()->update([
                        'estat' => 'baixa',
                        'data_baixa' => $data['data_baixa_efectiva'],
                        'observacions' => $data['observacions_baixa']
                    ]);
                    
                    // Dispatch offboarding
                    \App\Jobs\CrearChecklistOffboarding::dispatch($this->getRecord());
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Empleat donat de baixa')
                        ->success()
                        ->send();
                }),
                
            // Action per iniciar onboarding si no existeix
            Actions\Action::make('iniciar_onboarding')
                ->label('Iniciar Onboarding')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => 
                    $this->getRecord()->estat === 'actiu' && 
                    !$this->getRecord()->checklists()
                        ->whereHas('template', fn ($q) => $q->where('tipus', 'onboarding'))
                        ->exists()
                )
                ->action(function () {
                    \App\Jobs\CrearChecklistOnboarding::dispatch($this->getRecord());
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Onboarding iniciat')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informació Personal')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('nom_complet')
                                    ->label('Nom Complet')
                                    ->weight('bold')
                                    ->size('lg'),
                                    
                                Infolists\Components\TextEntry::make('nif')
                                    ->label('NIF')
                                    ->copyable(),
                                    
                                Infolists\Components\TextEntry::make('correu_personal')
                                    ->label('Correu Personal')
                                    ->copyable()
                                    ->url(fn ($record) => "mailto:{$record->correu_personal}"),
                                    
                                Infolists\Components\TextEntry::make('carrec')
                                    ->label('Càrrec'),
                            ]),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Informació Laboral')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('departament.nom')
                                    ->label('Departament')
                                    ->badge()
                                    ->color('info'),
                                    
                                Infolists\Components\TextEntry::make('estat')
                                    ->label('Estat')
                                    ->badge()
                                    ->color(fn (string $state) => match($state) {
                                        'actiu' => 'success',
                                        'baixa' => 'danger',
                                        'suspens' => 'warning',
                                    }),
                            ]),
                            
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('data_alta')
                                    ->label('Data Alta')
                                    ->dateTime('d/m/Y H:i'),
                                    
                                Infolists\Components\TextEntry::make('data_baixa')
                                    ->label('Data Baixa')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('N/A'),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Estadístiques')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('checklists_count')
                                    ->label('Checklists Totals')
                                    ->badge()
                                    ->color('info')
                                    ->getStateUsing(fn ($record) => $record->checklists()->count()),
                                    
                                Infolists\Components\TextEntry::make('checklists_completades')
                                    ->label('Checklists Completades')
                                    ->badge()
                                    ->color('success')
                                    ->getStateUsing(fn ($record) => 
                                        $record->checklists()->where('estat', 'completada')->count()
                                    ),
                                    
                                Infolists\Components\TextEntry::make('solicituds_count')
                                    ->label('Sol·licituds d\'Accés')
                                    ->badge()
                                    ->color('warning')
                                    ->getStateUsing(fn ($record) => $record->solicitudsAcces()->count()),
                                    
                                Infolists\Components\TextEntry::make('solicituds_aprovades')
                                    ->label('Sol·licituds Aprovades')
                                    ->badge()
                                    ->color('success')
                                    ->getStateUsing(fn ($record) => 
                                        $record->solicitudsAcces()->where('estat', 'aprovada')->count()
                                    ),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Observacions')
                    ->schema([
                        Infolists\Components\TextEntry::make('observacions')
                            ->label('')
                            ->placeholder('Sense observacions')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->observacions)),
                    
                Infolists\Components\Section::make('Informació del Sistema')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('identificador_unic')
                                    ->label('ID Sistema')
                                    ->copyable()
                                    ->badge()
                                    ->color('gray'),
                                    
                                Infolists\Components\TextEntry::make('usuariCreador.name')
                                    ->label('Creat per'),
                                    
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Data Creació')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ]),
            ]);
    }
}