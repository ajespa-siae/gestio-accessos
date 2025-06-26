<?php

namespace App\Filament\Resources\EmpleatResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\SolicitudAcces;
use App\Models\Sistema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\MaxWidth;

class SolicitudsAccesRelationManager extends RelationManager
{
    protected static string $relationship = 'solicitudsAcces';
    protected static ?string $title = 'Sol·licituds d\'Accés';
    protected static ?string $recordTitleAttribute = 'identificador_unic';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('usuari_solicitant_id')
                    ->relationship('usuariSolicitant', 'name')
                    ->required()
                    ->label('Sol·licitant')
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\Textarea::make('justificacio')
                    ->required()
                    ->label('Justificació')
                    ->rows(3)
                    ->placeholder('Motiu de la sol·licitud d\'accés...'),
                    
                Forms\Components\Select::make('estat')
                    ->options([
                        'pendent' => 'Pendent',
                        'validant' => 'Validant',
                        'aprovada' => 'Aprovada',
                        'rebutjada' => 'Rebutjada',
                        'finalitzada' => 'Finalitzada',
                    ])
                    ->required()
                    ->label('Estat')
                    ->disabled(fn (string $context) => $context === 'create'),
                    
                // Sistemes sol·licitats (per crear noves sol·licituds)
                Forms\Components\Repeater::make('sistemes_temporals')
                    ->label('Sistemes Sol·licitats')
                    ->schema([
                        Forms\Components\Select::make('sistema_id')
                            ->label('Sistema')
                            ->options(Sistema::where('actiu', true)->pluck('nom', 'id'))
                            ->required()
                            ->reactive()
                            ->searchable(),
                            
                        Forms\Components\Select::make('nivell_acces_id')
                            ->label('Nivell d\'Accés')
                            ->options(function (Forms\Get $get) {
                                $sistemaId = $get('sistema_id');
                                if (!$sistemaId) return [];
                                
                                return Sistema::find($sistemaId)
                                    ?->nivellsAcces()
                                    ->where('actiu', true)
                                    ->pluck('nom', 'id') ?? [];
                            })
                            ->required()
                            ->searchable(),
                    ])
                    ->visible(fn (string $context) => $context === 'create')
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('identificador_unic')
            ->columns([
                Tables\Columns\TextColumn::make('usuariSolicitant.name')
                    ->label('Sol·licitant')
                    ->searchable()
                    ->sortable()
                    ->limit(20),
                    
                Tables\Columns\BadgeColumn::make('estat')
                    ->label('Estat')
                    ->colors([
                        'warning' => 'pendent',
                        'info' => 'validant',
                        'success' => 'aprovada',
                        'danger' => 'rebutjada',
                        'gray' => 'finalitzada',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pendent',
                        'heroicon-o-cog-6-tooth' => 'validant',
                        'heroicon-o-check-circle' => 'aprovada',
                        'heroicon-o-x-circle' => 'rebutjada',
                        'heroicon-o-check-badge' => 'finalitzada',
                    ]),
                    
                Tables\Columns\TextColumn::make('sistemesSolicitats_count')
                    ->counts('sistemesSolicitats')
                    ->label('Sistemes')
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sol·licitada')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                    
                // Columnes ocultes per defecte
                Tables\Columns\TextColumn::make('identificador_unic')
                    ->label('ID Sol·licitud')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('validacions_count')
                    ->counts('validacions')
                    ->label('Validacions')
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('validacions_aprovades')
                    ->label('Aprovades')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn (SolicitudAcces $record) => 
                        $record->validacions()->where('estat', 'aprovada')->count()
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('justificacio')
                    ->label('Justificació')
                    ->limit(30)
                    ->tooltip(fn (SolicitudAcces $record) => $record->justificacio)
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('data_finalitzacio')
                    ->label('Finalitzada')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('Pendent')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estat')
                    ->options([
                        'pendent' => 'Pendent',
                        'validant' => 'Validant',
                        'aprovada' => 'Aprovada',
                        'rebutjada' => 'Rebutjada',
                        'finalitzada' => 'Finalitzada',
                    ]),
                    
                Tables\Filters\SelectFilter::make('usuari_solicitant')
                    ->relationship('usuariSolicitant', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova Sol·licitud')
                    ->modalWidth(MaxWidth::Large)
                    ->mutateFormDataUsing(function (array $data) {
                        $data['empleat_destinatari_id'] = $this->ownerRecord->id;
                        return $data;
                    })
                    ->using(function (array $data) {
                        // Crear la sol·licitud principal
                        $solicitud = SolicitudAcces::create([
                            'empleat_destinatari_id' => $data['empleat_destinatari_id'],
                            'usuari_solicitant_id' => $data['usuari_solicitant_id'],
                            'justificacio' => $data['justificacio'],
                            'estat' => 'pendent',
                            'identificador_unic' => 'SOL-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 8))
                        ]);
                        
                        // Crear sistemes sol·licitats
                        if (isset($data['sistemes_temporals'])) {
                            foreach ($data['sistemes_temporals'] as $sistema) {
                                $solicitud->sistemesSolicitats()->create([
                                    'sistema_id' => $sistema['sistema_id'],
                                    'nivell_acces_id' => $sistema['nivell_acces_id'],
                                ]);
                            }
                        }
                        
                        // Dispatch Job per crear validacions
                        \App\Jobs\CrearValidacionsSolicitud::dispatch($solicitud);
                        
                        return $solicitud;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Veure'),
                    
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->disabled(fn (SolicitudAcces $record) => 
                        in_array($record->estat, ['aprovada', 'rebutjada', 'finalitzada'])
                    ),
                    
                Action::make('veure_validacions')
                    ->label('Validacions')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->url(fn (SolicitudAcces $record) => 
                        route('filament.admin.resources.solicituds-acces.view', $record)
                    )
                    ->openUrlInNewTab(),
                    
                Action::make('processar_manualment')
                    ->label('Processar')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('warning')
                    ->visible(fn (SolicitudAcces $record) => $record->estat === 'pendent')
                    ->requiresConfirmation()
                    ->modalHeading('Processar Sol·licitud Manualment')
                    ->modalDescription('Aquesta acció crearà les validacions necessàries per aquesta sol·licitud.')
                    ->action(function (SolicitudAcces $record) {
                        \App\Jobs\CrearValidacionsSolicitud::dispatch($record);
                        
                        Notification::make()
                            ->title('Sol·licitud processada')
                            ->body('S\'han creat les validacions necessàries.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->rol_principal === 'admin'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}