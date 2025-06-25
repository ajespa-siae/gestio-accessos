<?php

namespace App\Filament\Resources\EmpleatResource\Pages;

use App\Filament\Resources\EmpleatResource;
use App\Models\Empleat;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BadgeEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Grid;

class ViewEmpleat extends ViewRecord
{
    protected static string $resource = EmpleatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('donar_baixa')
                ->label('Donar de Baixa')
                ->icon('heroicon-o-user-minus')
                ->color('danger')
                ->visible(fn (Empleat $record): bool => $record->estat === 'actiu')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Baixa d\'Empleat')
                ->modalDescription(fn (Empleat $record) => 
                    "Esteu segur que voleu donar de baixa a {$record->nom_complet}?"
                )
                ->form([
                    \Filament\Forms\Components\Textarea::make('observacions_baixa')
                        ->label('Observacions de la baixa')
                        ->required()
                ])
                ->action(function (Empleat $record, array $data): void {
                    $record->donarBaixa($data['observacions_baixa']);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Empleat donat de baixa')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Split::make([
                    Grid::make(2)
                        ->schema([
                            Section::make('Informació Personal')
                                ->schema([
                                    TextEntry::make('nom_complet')
                                        ->label('Nom Complet'),
                                    TextEntry::make('nif')
                                        ->label('NIF'),
                                    TextEntry::make('correu_personal')
                                        ->label('Correu Personal')
                                        ->copyable(),
                                ])
                                ->columnSpan(1),
                                
                            Section::make('Informació Laboral')
                                ->schema([
                                    TextEntry::make('departament.nom')
                                        ->label('Departament'),
                                    TextEntry::make('carrec')
                                        ->label('Càrrec'),
                                    BadgeEntry::make('estat')
                                        ->label('Estat')
                                        ->colors([
                                            'success' => 'actiu',
                                            'danger' => 'baixa',
                                            'warning' => 'suspens',
                                        ]),
                                ])
                                ->columnSpan(1),
                        ]),
                        
                    Section::make('Dates i Seguiment')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('data_alta')
                                        ->label('Data d\'Alta')
                                        ->date('d/m/Y'),
                                    TextEntry::make('data_baixa')
                                        ->label('Data de Baixa')
                                        ->date('d/m/Y')
                                        ->placeholder('—'),
                                    TextEntry::make('identificador_unic')
                                        ->label('Identificador Únic')
                                        ->copyable(),
                                    TextEntry::make('usuariCreador.name')
                                        ->label('Creat per'),
                                ]),
                        ]),
                        
                    Section::make('Resum d\'Activitat')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextEntry::make('checklists_count')
                                        ->label('Total Checklists')
                                        ->getStateUsing(fn ($record) => $record->checklists()->count()),
                                    TextEntry::make('checklists_pendents')
                                        ->label('Checklists Pendents')
                                        ->getStateUsing(fn ($record) => 
                                            $record->checklists()->where('estat', '!=', 'completada')->count()
                                        )
                                        ->badge()
                                        ->color('warning'),
                                    TextEntry::make('solicituds_count')
                                        ->label('Sol·licituds d\'Accés')
                                        ->getStateUsing(fn ($record) => $record->solicitudsAcces()->count()),
                                ]),
                        ]),
                ]),
                
                Section::make('Observacions')
                    ->schema([
                        TextEntry::make('observacions')
                            ->label('')
                            ->placeholder('Sense observacions')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => empty($record->observacions)),
            ]);
    }
}