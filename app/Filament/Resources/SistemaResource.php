<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SistemaResource\Pages;
use App\Filament\Resources\SistemaResource\RelationManagers;
use App\Models\Sistema;
use App\Models\User;
use App\Models\Departament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Collection;

class SistemaResource extends Resource
{
    protected static ?string $model = Sistema::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    
    protected static ?string $navigationGroup = 'Configuració';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'nom';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Informació del Sistema')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nom')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Nom del Sistema')
                                    ->unique(Sistema::class, 'nom', ignoreRecord: true)
                                    ->columnSpanFull(),
                                
                                Forms\Components\Textarea::make('descripcio')
                                    ->maxLength(500)
                                    ->label('Descripció')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                
                                Forms\Components\Toggle::make('actiu')
                                    ->default(true)
                                    ->label('Sistema Actiu'),
                            ]),
                    ]),

                Fieldset::make('Configuració d\'Accés')
                    ->schema([
                        // Nivells d'accés (per crear nous sistemes)
                        Forms\Components\Repeater::make('nivells_temporals')
                            ->label('Nivells d\'Accés')
                            ->schema([
                                Forms\Components\TextInput::make('nom')
                                    ->required()
                                    ->label('Nom del Nivell')
                                    ->placeholder('Consulta, Gestió, Supervisor...'),
                                
                                Forms\Components\Textarea::make('descripcio')
                                    ->label('Descripció')
                                    ->rows(2),
                                
                                Forms\Components\TextInput::make('ordre')
                                    ->numeric()
                                    ->default(1)
                                    ->label('Ordre')
                                    ->minValue(1),
                                
                                Forms\Components\Toggle::make('actiu')
                                    ->default(true)
                                    ->label('Actiu'),
                            ])
                            ->visible(fn (string $context) => $context === 'create')
                            ->defaultItems(3)
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed(),
                        
                        // Validadors (per crear nous sistemes)
                        Forms\Components\Repeater::make('validadors_temporals')
                            ->label('Validadors del Sistema')
                            ->schema([
                                Forms\Components\Select::make('validador_id')
                                    ->label('Validador')
                                    ->options(User::where('actiu', true)
                                        ->whereIn('rol_principal', ['admin', 'rrhh', 'it', 'gestor'])
                                        ->pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),
                                
                                Forms\Components\TextInput::make('ordre')
                                    ->numeric()
                                    ->default(1)
                                    ->label('Ordre de Validació')
                                    ->minValue(1),
                                
                                Forms\Components\Toggle::make('requerit')
                                    ->default(true)
                                    ->label('Validació Obligatòria'),
                                
                                Forms\Components\Toggle::make('actiu')
                                    ->default(true)
                                    ->label('Actiu'),
                            ])
                            ->visible(fn (string $context) => $context === 'create')
                            ->defaultItems(1)
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed(),
                        
                        // Departaments amb accés (per crear nous sistemes)
                        Forms\Components\CheckboxList::make('departaments_temporals')
                            ->label('Departaments amb Accés')
                            ->options(Departament::where('actiu', true)->pluck('nom', 'id'))
                            ->visible(fn (string $context) => $context === 'create')
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (string $context) => $context === 'create'),

                // Informació del sistema (només en edició)
                Fieldset::make('Estadístiques')
                    ->schema([
                        Forms\Components\Placeholder::make('nivells_count')
                            ->label('Nivells d\'Accés')
                            ->content(function ($get, $record) {
                                return $record ? $record->nivellsAcces()->count() : 0;
                            }),
                        
                        Forms\Components\Placeholder::make('validadors_count')
                            ->label('Validadors Configurats')
                            ->content(function ($get, $record) {
                                return $record ? $record->validadors()->count() : 0;
                            }),
                        
                        Forms\Components\Placeholder::make('departaments_count')
                            ->label('Departaments amb Accés')
                            ->content(function ($get, $record) {
                                return $record ? $record->departaments()->count() : 0;
                            }),
                        
                        Forms\Components\Placeholder::make('solicituds_count')
                            ->label('Sol·licituds Totals')
                            ->content(function ($get, $record) {
                                // Temporal: comentat fins implementar SolicitudSistema
                                // return $record ? \App\Models\SolicitudSistema::where('sistema_id', $record->id)->count() : 0;
                                return 0;
                            }),
                    ])
                    ->visible(fn (string $context) => $context === 'edit')
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom del Sistema')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('descripcio')
                    ->label('Descripció')
                    ->limit(40)
                    ->tooltip(fn (Sistema $record) => $record->descripcio),
                
                Tables\Columns\IconColumn::make('actiu')
                    ->label('Actiu')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('nivellsAcces_count')
                    ->counts('nivellsAcces')
                    ->label('Nivells')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('validadors_count')
                    ->counts('validadors')
                    ->label('Validadors')
                    ->badge()
                    ->color('warning'),
                
                // Columnes ocultes per defecte
                Tables\Columns\TextColumn::make('departaments_count')
                    ->counts('departaments')
                    ->label('Departaments')
                    ->badge()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('solicituds_totals')
                    ->label('Sol·licituds')
                    ->badge()
                    ->color('gray')
                    ->getStateUsing(function (Sistema $record) {
                        // Temporal: comentat fins implementar SolicitudSistema
                        // return \App\Models\SolicitudSistema::where('sistema_id', $record->id)->count();
                        return 0;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creat')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualitzat')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('actiu')
                    ->options([
                        1 => 'Actius',
                        0 => 'Inactius',
                    ])
                    ->default(1),
                
                Filter::make('sense_validadors')
                    ->label('Sense Validadors')
                    ->query(fn (Builder $query) => $query->doesntHave('validadors')),
                
                Filter::make('sense_nivells')
                    ->label('Sense Nivells d\'Accés')
                    ->query(fn (Builder $query) => $query->doesntHave('nivellsAcces')),
                
                Filter::make('molt_solicitats')
                    ->label('Més de 5 Sol·licituds')
                    ->query(function (Builder $query) {
                        // Temporal: comentat fins implementar SolicitudSistema
                        // return $query->whereHas('solicitudSistemes', function ($q) {
                        //     $q->selectRaw('count(*)')
                        //       ->havingRaw('count(*) > 5');
                        // });
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Veure'),
                
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                
                // Action per activar/desactivar ràpidament
                Action::make('toggle_actiu')
                    ->label(function (Sistema $record) {
                        return $record->actiu ? 'Desactivar' : 'Activar';
                    })
                    ->icon(function (Sistema $record) {
                        return $record->actiu ? 'heroicon-o-eye-slash' : 'heroicon-o-eye';
                    })
                    ->color(function (Sistema $record) {
                        return $record->actiu ? 'danger' : 'success';
                    })
                    ->requiresConfirmation()
                    ->modalHeading(function (Sistema $record) {
                        return ($record->actiu ? 'Desactivar' : 'Activar') . ' Sistema';
                    })
                    ->modalDescription(function (Sistema $record) {
                        return $record->actiu 
                            ? 'Aquest sistema ja no estarà disponible per sol·licituds d\'accés.' 
                            : 'Aquest sistema tornarà a estar disponible per sol·licituds d\'accés.';
                    })
                    ->action(function (Sistema $record) {
                        $record->update(['actiu' => !$record->actiu]);
                        
                        Notification::make()
                            ->title('Sistema ' . ($record->actiu ? 'activat' : 'desactivat'))
                            ->body("El sistema {$record->nom} ha estat " . 
                                  ($record->actiu ? 'activat' : 'desactivat') . " correctament.")
                            ->success()
                            ->send();
                    }),
                
                // Action per clonar sistema
                Action::make('clonar')
                    ->label('Clonar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('nou_nom')
                            ->label('Nom del Nou Sistema')
                            ->required()
                            ->placeholder('Còpia del sistema'),
                        
                        Forms\Components\Toggle::make('clonar_nivells')
                            ->label('Clonar Nivells d\'Accés')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('clonar_validadors')
                            ->label('Clonar Validadors')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('clonar_departaments')
                            ->label('Clonar Departaments')
                            ->default(false),
                    ])
                    ->modalWidth(MaxWidth::Medium)
                    ->action(function (Sistema $record, array $data) {
                        $nouSistema = Sistema::create([
                            'nom' => $data['nou_nom'],
                            'descripcio' => $record->descripcio . ' (Còpia)',
                            'actiu' => false, // Crear inactiu per defecte
                        ]);
                        
                        // Clonar nivells d'accés
                        if ($data['clonar_nivells']) {
                            foreach ($record->nivellsAcces as $nivell) {
                                $nouSistema->nivellsAcces()->create([
                                    'nom' => $nivell->nom,
                                    'descripcio' => $nivell->descripcio,
                                    'ordre' => $nivell->ordre,
                                    'actiu' => $nivell->actiu,
                                ]);
                            }
                        }
                        
                        // Clonar validadors
                        if ($data['clonar_validadors']) {
                            foreach ($record->validadors as $validador) {
                                $nouSistema->validadors()->attach($validador->id, [
                                    'ordre' => $validador->pivot->ordre,
                                    'requerit' => $validador->pivot->requerit,
                                    'actiu' => $validador->pivot->actiu,
                                ]);
                            }
                        }
                        
                        // Clonar departaments
                        if ($data['clonar_departaments']) {
                            foreach ($record->departaments as $departament) {
                                $nouSistema->departaments()->attach($departament->id, [
                                    'acces_per_defecte' => $departament->pivot->acces_per_defecte,
                                ]);
                            }
                        }
                        
                        Notification::make()
                            ->title('Sistema clonat correctament')
                            ->body("S'ha creat el sistema '{$data['nou_nom']}' com a còpia.")
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('veure')
                                    ->label('Veure Sistema')
                                    ->url(self::getUrl('edit', ['record' => $nouSistema])),
                            ])
                            ->persistent()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk action per activar sistemes
                    BulkAction::make('activar')
                        ->label('Activar Sistemes')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(fn ($record) => $record->update(['actiu' => true]));
                            
                            Notification::make()
                                ->title('Sistemes activats')
                                ->body("S'han activat {$records->count()} sistemes.")
                                ->success()
                                ->send();
                        }),
                    
                    // Bulk action per desactivar sistemes
                    BulkAction::make('desactivar')
                        ->label('Desactivar Sistemes')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn ($record) => $record->update(['actiu' => false]));
                            
                            Notification::make()
                                ->title('Sistemes desactivats')
                                ->body("S'han desactivat {$records->count()} sistemes.")
                                ->success()
                                ->send();
                        }),
                    
                    // Bulk action per assignar a departament
                    BulkAction::make('assignar_departament')
                        ->label('Assignar a Departament')
                        ->icon('heroicon-o-building-office')
                        ->form([
                            Forms\Components\Select::make('departament_id')
                                ->label('Departament')
                                ->options(Departament::where('actiu', true)->pluck('nom', 'id'))
                                ->required(),
                            
                            Forms\Components\Toggle::make('acces_per_defecte')
                                ->label('Accés per Defecte')
                                ->default(false),
                        ])
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $sistema) {
                                $sistema->departaments()->syncWithoutDetaching([
                                    $data['departament_id'] => [
                                        'acces_per_defecte' => $data['acces_per_defecte']
                                    ]
                                ]);
                            }
                            
                            $departament = Departament::find($data['departament_id']);
                            
                            Notification::make()
                                ->title('Sistemes assignats')
                                ->body("S'han assignat {$records->count()} sistemes al departament {$departament->nom}.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('nom')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }
    
    public static function getRelations(): array
    {
        return [
            RelationManagers\NivellsAccesRelationManager::class,
            RelationManagers\ValidadorsRelationManager::class,
            RelationManagers\DepartamentsRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSistemes::route('/'),
            'create' => Pages\CreateSistema::route('/create'),
            'view' => Pages\ViewSistema::route('/{record}'),
            'edit' => Pages\EditSistema::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('actiu', true)->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $actius = static::getModel()::where('actiu', true)->count();
        $total = static::getModel()::count();
        
        if ($actius === $total) {
            return 'success';
        }
        if ($actius > 0) {
            return 'warning';
        }
        return 'danger';
    }
    
    // Query personalitzat per optimitzar carregues
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['nivellsAcces', 'validadors', 'departaments']);
    }
}