<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmpleatResource\Pages;
use App\Filament\Resources\EmpleatResource\RelationManagers;
use App\Models\Empleat;
use App\Models\Departament;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Collection;

class EmpleatResource extends Resource
{
    protected static ?string $model = Empleat::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'Gestió RRHH';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'nom_complet';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Dades Personals')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nom_complet')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Nom Complet')
                                    ->columnSpanFull(),
                                
                                Forms\Components\TextInput::make('nif')
                                    ->required()
                                    ->maxLength(20)
                                    ->label('NIF')
                                    ->unique(Empleat::class, 'nif', ignoreRecord: true)
                                    ->validationMessages([
                                        'unique' => 'Ja existeix un empleat amb aquest NIF.',
                                    ]),
                                
                                Forms\Components\TextInput::make('correu_personal')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Correu Personal'),
                            ]),
                    ]),

                Fieldset::make('Informació Laboral')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('departament_id')
                                    ->relationship('departament', 'nom')
                                    ->required()
                                    ->label('Departament')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nom')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('descripcio')
                                            ->maxLength(500),
                                    ]),
                                
                                Forms\Components\TextInput::make('carrec')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Càrrec'),
                            ]),
                    ]),

                Fieldset::make('Estat i Observacions')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('estat')
                                    ->options([
                                        'actiu' => 'Actiu',
                                        'baixa' => 'Baixa',
                                        'suspens' => 'Suspens',
                                    ])
                                    ->default('actiu')
                                    ->required()
                                    ->label('Estat')
                                    ->disabled(fn (string $context) => $context === 'create'),
                                
                                Forms\Components\DateTimePicker::make('data_baixa')
                                    ->label('Data Baixa')
                                    ->visible(fn (Forms\Get $get) => $get('estat') === 'baixa'),
                            ]),
                        
                        Forms\Components\Textarea::make('observacions')
                            ->maxLength(65535)
                            ->label('Observacions')
                            ->columnSpanFull(),
                    ]),

                // Campos automáticos (solo lectura en edit)
                Fieldset::make('Informació Sistema')
                    ->schema([
                        Forms\Components\TextInput::make('identificador_unic')
                            ->label('Identificador Únic')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\DateTimePicker::make('data_alta')
                            ->label('Data Alta')
                            ->disabled()
                            ->dehydrated(false),
                            
                        Forms\Components\Select::make('usuari_creador_id')
                            ->relationship('usuariCreador', 'name')
                            ->label('Creat per')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->visible(fn (string $context) => $context === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom_complet')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('nif')
                    ->label('NIF')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('departament.nom')
                    ->label('Departament')
                    ->sortable()
                    ->searchable()
                    ->limit(20),
                
                Tables\Columns\BadgeColumn::make('estat')
                    ->label('Estat')
                    ->colors([
                        'success' => 'actiu',
                        'danger' => 'baixa',
                        'warning' => 'suspens',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'actiu',
                        'heroicon-o-x-circle' => 'baixa',
                        'heroicon-o-pause-circle' => 'suspens',
                    ]),
                
                Tables\Columns\TextColumn::make('checklists_count')
                    ->counts('checklists')
                    ->label('Checklists')
                    ->badge()
                    ->color('info'),
                
                // Columnes ocultes per defecte (toggleable)
                Tables\Columns\TextColumn::make('carrec')
                    ->label('Càrrec')
                    ->searchable()
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('solicitudsAcces_count')
                    ->counts('solicitudsAcces')
                    ->label('Sol·licituds')
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('correu_personal')
                    ->label('Correu')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('data_alta')
                    ->label('Data Alta')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('data_baixa')
                    ->label('Data Baixa')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estat')
                    ->options([
                        'actiu' => 'Actiu',
                        'baixa' => 'Baixa',
                        'suspens' => 'Suspens',
                    ])
                    ->default('actiu'),
                    
                SelectFilter::make('departament')
                    ->relationship('departament', 'nom')
                    ->searchable()
                    ->preload(),
                
                Filter::make('amb_checklists_pendents')
                    ->label('Amb Checklists Pendents')
                    ->query(fn (Builder $query) => $query->whereHas('checklists', function ($q) {
                        $q->where('estat', '!=', 'completada');
                    })),
                
                Filter::make('sense_solicituds')
                    ->label('Sense Sol·licituds d\'Accés')
                    ->query(fn (Builder $query) => $query->doesntHave('solicitudsAcces')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Veure'),
                
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                
                // Action personalitzat per Onboarding manual
                Action::make('iniciar_onboarding')
                    ->label('Iniciar Onboarding')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (Empleat $record) => 
                        $record->estat === 'actiu' && 
                        !$record->teChecklistOnboarding()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Iniciar Procés d\'Onboarding')
                    ->modalDescription('Es crearà una checklist d\'onboarding per aquest empleat i es notificarà a IT.')
                    ->action(function (Empleat $record) {
                        // Dispatch del Job que ja tens implementat
                        \App\Jobs\CrearChecklistOnboarding::dispatch($record);
                        
                        Notification::make()
                            ->title('Onboarding iniciat')
                            ->body("S'ha iniciat el procés d'onboarding per {$record->nom_complet}")
                            ->success()
                            ->send();
                    }),
                
                // Action per Offboarding
                Action::make('iniciar_offboarding')
                    ->label('Donar de Baixa')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Empleat $record) => $record->estat === 'actiu')
                    ->form([
                        Forms\Components\Textarea::make('observacions_baixa')
                            ->label('Motiu de la baixa')
                            ->required()
                            ->placeholder('Descriure el motiu de la baixa...')
                            ->rows(3),
                        
                        Forms\Components\DateTimePicker::make('data_baixa_efectiva')
                            ->label('Data efectiva de baixa')
                            ->default(now())
                            ->required(),
                    ])
                    ->modalWidth(MaxWidth::Medium)
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Baixa d\'Empleat')
                    ->modalDescription('Aquesta acció crearà un procés d\'offboarding i marcarà l\'empleat com a baixa.')
                    ->action(function (Empleat $record, array $data) {
                        $record->update([
                            'estat' => 'baixa',
                            'data_baixa' => $data['data_baixa_efectiva'],
                            'observacions' => $data['observacions_baixa']
                        ]);
                        
                        // Dispatch del Job d'offboarding si l'has implementat
                        \App\Jobs\CrearChecklistOffboarding::dispatch($record);
                        
                        Notification::make()
                            ->title('Empleat donat de baixa')
                            ->body("S'ha processat la baixa de {$record->nom_complet}")
                            ->success()
                            ->send();
                    }),
                
                // Action per veure auditoria (comentat fins implementar página)
                // Action::make('veure_auditoria')
                //     ->label('Auditoria')
                //     ->icon('heroicon-o-document-text')
                //     ->color('gray')
                //     ->url(fn (Empleat $record) => route('filament.admin.pages.auditoria', [
                //         'identificador' => $record->identificador_unic
                //     ]))
                //     ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk action per exportar
                    BulkAction::make('exportar')
                        ->label('Exportar Seleccionats')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            // Aquí pots implementar exportació Excel/CSV
                            Notification::make()
                                ->title('Exportació en procés')
                                ->body('Els empleats seleccionats s\'exportaran en breu')
                                ->info()
                                ->send();
                        }),
                    
                    // Bulk action per actualitzar departament
                    BulkAction::make('canviar_departament')
                        ->label('Canviar Departament')
                        ->icon('heroicon-o-building-office')
                        ->form([
                            Forms\Components\Select::make('nou_departament_id')
                                ->label('Nou Departament')
                                ->options(Departament::where('actiu', true)->pluck('nom', 'id'))
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(function ($empleat) use ($data) {
                                $empleat->update(['departament_id' => $data['nou_departament_id']]);
                            });
                            
                            Notification::make()
                                ->title('Departament actualitzat')
                                ->body('Els empleats seleccionats han canviat de departament')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('data_alta', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }
    
    public static function getRelations(): array
    {
        return [
            RelationManagers\ChecklistsRelationManager::class,
            RelationManagers\SolicitudsAccesRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmpleats::route('/'),
            'create' => Pages\CreateEmpleat::route('/create'),
            'view' => Pages\ViewEmpleat::route('/{record}'),
            'edit' => Pages\EditEmpleat::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('estat', 'actiu')->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('estat', 'actiu')->count() > 0 ? 'success' : 'gray';
    }
    
    // Query personalitzat per optimitzar carregues
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['departament', 'usuariCreador'])
            ->withCount(['checklists', 'solicitudsAcces']);
    }
}