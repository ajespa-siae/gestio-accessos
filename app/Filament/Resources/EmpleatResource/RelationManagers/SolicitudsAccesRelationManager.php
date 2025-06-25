<?php

namespace App\Filament\Resources\EmpleatResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use App\Models\Sistema;
use App\Models\NivellAccesSistema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SolicitudsAccesRelationManager extends RelationManager
{
    protected static string $relationship = 'solicitudsAcces';

    protected static ?string $title = 'Sol·licituds d\'Accés';

    protected static ?string $modelLabel = 'Sol·licitud';

    protected static ?string $pluralModelLabel = 'Sol·licituds';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('justificacio')
                    ->label('Justificació')
                    ->required()
                    ->maxLength(1000)
                    ->helperText('Indiqueu el motiu pel qual es necessiten aquests accessos')
                    ->columnSpanFull(),
                    
                Repeater::make('sistemes_solicitats')
                    ->label('Sistemes Sol·licitats')
                    ->schema([
                        Select::make('sistema_id')
                            ->label('Sistema')
                            ->options(Sistema::where('actiu', true)->pluck('nom', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('nivell_acces_id', null);
                            }),
                            
                        Select::make('nivell_acces_id')
                            ->label('Nivell d\'Accés')
                            ->options(function (callable $get) {
                                $sistemaId = $get('sistema_id');
                                if (!$sistemaId) return [];
                                
                                return NivellAccesSistema::where('sistema_id', $sistemaId)
                                    ->where('actiu', true)
                                    ->orderBy('ordre')
                                    ->pluck('nom', 'id');
                            })
                            ->required()
                            ->reactive(),
                    ])
                    ->minItems(1)
                    ->maxItems(10)
                    ->collapsible()
                    ->columnSpanFull()
                    ->addActionLabel('Afegir Sistema'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('identificador_unic')
            ->columns([
                TextColumn::make('identificador_unic')
                    ->label('ID Sol·licitud')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Clic per copiar'),
                    
                BadgeColumn::make('estat')
                    ->label('Estat')
                    ->colors([
                        'gray' => 'pendent',
                        'warning' => 'validant',
                        'success' => 'aprovada',
                        'danger' => 'rebutjada',
                        'info' => 'finalitzada'
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pendent',
                        'heroicon-o-exclamation-triangle' => 'validant',
                        'heroicon-o-check-circle' => 'aprovada',
                        'heroicon-o-x-circle' => 'rebutjada',
                        'heroicon-o-check-badge' => 'finalitzada'
                    ]),
                    
                TextColumn::make('sistemes_resum')
                    ->label('Sistemes Sol·licitats')
                    ->getStateUsing(function ($record) {
                        $sistemes = $record->sistemesSolicitats()->with('sistema')->get();
                        return $sistemes->pluck('sistema.nom')->take(3)->join(', ') . 
                               ($sistemes->count() > 3 ? ' i ' . ($sistemes->count() - 3) . ' més' : '');
                    })
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->sistemesSolicitats()->with(['sistema', 'nivellAcces'])
                            ->get()
                            ->map(fn($item) => "{$item->sistema->nom} ({$item->nivellAcces->nom})")
                            ->join(', ');
                    }),
                    
                TextColumn::make('usuariSolicitant.name')
                    ->label('Sol·licitant')
                    ->toggleable(),
                    
                TextColumn::make('validacions_pendents')
                    ->label('Validacions')
                    ->getStateUsing(function ($record) {
                        $total = $record->validacions()->count();
                        $pendents = $record->validacions()->where('estat', 'pendent')->count();
                        $aprovades = $record->validacions()->where('estat', 'aprovada')->count();
                        
                        return "{$aprovades}/{$total}";
                    })
                    ->badge()
                    ->color(function ($record) {
                        $total = $record->validacions()->count();
                        $aprovades = $record->validacions()->where('estat', 'aprovada')->count();
                        
                        if ($aprovades === $total) return 'success';
                        if ($aprovades > 0) return 'warning';
                        return 'gray';
                    }),
                    
                TextColumn::make('created_at')
                    ->label('Sol·licitada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('data_finalitzacio')
                    ->label('Finalitzada')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('estat')
                    ->options([
                        'pendent' => 'Pendent',
                        'validant' => 'En Validació',
                        'aprovada' => 'Aprovada',
                        'rebutjada' => 'Rebutjada',
                        'finalitzada' => 'Finalitzada'
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova Sol·licitud')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['usuari_solicitant_id'] = Auth::id();
                        $data['estat'] = 'pendent';
                        return $data;
                    })
                    ->using(function (array $data, string $model) {
                        // Crear la sol·licitud
                        $solicitud = $model::create([
                            'empleat_destinatari_id' => $this->getOwnerRecord()->id,
                            'usuari_solicitant_id' => $data['usuari_solicitant_id'],
                            'estat' => $data['estat'],
                            'justificacio' => $data['justificacio'],
                        ]);
                        
                        // Crear els sistemes sol·licitats
                        foreach ($data['sistemes_solicitats'] as $sistema) {
                            $solicitud->sistemesSolicitats()->create([
                                'sistema_id' => $sistema['sistema_id'],
                                'nivell_acces_id' => $sistema['nivell_acces_id'],
                            ]);
                        }
                        
                        return $solicitud;
                    })
                    ->successNotificationTitle('Sol·licitud creada correctament'),
            ])
            ->actions([
                Action::make('veure_detalls')
                    ->label('Veure Detalls')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => 
                        route('filament.admin.resources.solicitud-acces.view', $record)
                    ),
                    
                Action::make('veure_validacions')
                    ->label('Validacions')
                    ->icon('heroicon-o-shield-check')
                    ->color('warning')
                    ->visible(fn ($record) => $record->validacions()->exists())
                    ->url(fn ($record) => 
                        route('filament.admin.resources.validacions.index', [
                            'tableFilters[solicitud_id][value]' => $record->id
                        ])
                    ),
                    
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => in_array($record->estat, ['pendent', 'validant'])),
                    
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->estat === 'pendent')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}