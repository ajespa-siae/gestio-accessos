<?php
// app/Filament/Resources/EmpleatResource.php

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
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EmpleatResource extends Resource
{
    protected static ?string $model = Empleat::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Empleats';
    
    protected static ?string $modelLabel = 'Empleat';
    
    protected static ?string $pluralModelLabel = 'Empleats';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dades Personals')
                    ->schema([
                        TextInput::make('nom_complet')
                            ->required()
                            ->maxLength(255)
                            ->label('Nom Complet'),
                            
                        TextInput::make('nif')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->label('NIF/Employee ID')
                            ->helperText('Aquest camp s\'utilitzarà per sincronitzar amb LDAP'),
                            
                        TextInput::make('correu_personal')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->label('Correu Personal'),
                    ])
                    ->columns(2),
                    
                Section::make('Dades Laborals')
                    ->schema([
                        Select::make('departament_id')
                            ->label('Departament')
                            ->options(function() {
                                return Departament::query()
                                    ->select(['id', 'nom'])
                                    ->where('actiu', true)
                                    ->get()
                                    ->pluck('nom', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable(),
                            
                        TextInput::make('carrec')
                            ->required()
                            ->maxLength(255)
                            ->label('Càrrec'),
                            
                        Select::make('estat')
                            ->options([
                                'actiu' => 'Actiu',
                                'baixa' => 'Baixa',
                                'suspens' => 'Suspès'
                            ])
                            ->default('actiu')
                            ->required()
                            ->disabled(fn ($operation) => $operation === 'create'),
                            
                        DatePicker::make('data_alta')
                            ->default(now())
                            ->required()
                            ->label('Data d\'Alta')
                            ->disabled(fn ($operation) => $operation === 'create'),
                            
                        DatePicker::make('data_baixa')
                            ->label('Data de Baixa')
                            ->visible(fn ($get) => $get('estat') === 'baixa'),
                    ])
                    ->columns(2),
                    
                Section::make('Informació Addicional')
                    ->schema([
                        Textarea::make('observacions')
                            ->maxLength(1000)
                            ->label('Observacions')
                            ->columnSpanFull(),
                            
                        TextInput::make('identificador_unic')
                            ->label('Identificador Únic')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($operation) => $operation === 'edit'),
                            
                        Select::make('usuari_creador_id')
                            ->label('Creat per')
                            ->options(User::where('actiu', true)->pluck('name', 'id'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($operation) => $operation === 'edit'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom_complet')
                    ->searchable()
                    ->label('Nom Complet')
                    ->sortable(),
                    
                TextColumn::make('nif')
                    ->searchable()
                    ->label('NIF')
                    ->toggleable(),
                    
                TextColumn::make('departament.nom')
                    ->searchable()
                    ->label('Departament')
                    ->sortable(),
                    
                TextColumn::make('carrec')
                    ->searchable()
                    ->label('Càrrec')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),
                    
                BadgeColumn::make('estat')
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
                    
                TextColumn::make('checklists_count')
                    ->counts([
                        'checklists' => fn (Builder $query) => $query->where('estat', '!=', 'completada')
                    ])
                    ->label('Checklists Pendents')
                    ->badge()
                    ->color('warning'),
                    
                TextColumn::make('solicituds_count')
                    ->counts([
                        'solicitudsAcces' => fn (Builder $query) => $query->where('estat', 'pendent')
                    ])
                    ->label('Sol·licituds Pendents')
                    ->badge()
                    ->color('info'),
                    
                TextColumn::make('usuariCreador.name')
                    ->label('Creat per')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                    
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Creat el')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                SelectFilter::make('estat')
                    ->options([
                        'actiu' => 'Actiu',
                        'baixa' => 'Baixa',
                        'suspens' => 'Suspès'
                    ])
                    ->default('actiu'),
                    
                SelectFilter::make('departament')
                    ->relationship('departament', 'nom')
                    ->searchable()
                    ->preload(),
                    
                Filter::make('data_alta')
                    ->form([
                        DatePicker::make('desde')
                            ->label('Des de'),
                        DatePicker::make('fins')
                            ->label('Fins a'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('data_alta', '>=', $date),
                            )
                            ->when(
                                $data['fins'],
                                fn (Builder $query, $date): Builder => $query->whereDate('data_alta', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        
                        if ($data['desde']) {
                            $indicators[] = 'Des de: ' . Carbon::parse($data['desde'])->format('d/m/Y');
                        }
                        
                        if ($data['fins']) {
                            $indicators[] = 'Fins: ' . Carbon::parse($data['fins'])->format('d/m/Y');
                        }
                        
                        return $indicators;
                    }),
                    
                Filter::make('sense_baixa')
                    ->label('Només actius')
                    ->query(fn (Builder $query): Builder => $query->where('estat', 'actiu'))
                    ->default(),
                    
                Filter::make('checklists_pendents')
                    ->label('Amb checklists pendents')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('checklists', fn ($q) => $q->where('estat', '!=', 'completada'))
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    //Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Action::make('donar_baixa')
                        ->label('Donar de Baixa')
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->visible(fn (Empleat $record): bool => $record->estat === 'actiu')
                        ->requiresConfirmation()
                        ->modalHeading('Confirmar Baixa d\'Empleat')
                        ->modalDescription(fn (Empleat $record) => 
                            "Esteu segur que voleu donar de baixa a {$record->nom_complet}? Això crearà automàticament les tasques d'offboarding."
                        )
                        ->form([
                            Textarea::make('observacions_baixa')
                                ->label('Observacions de la baixa')
                                ->required()
                                ->helperText('Indiqueu el motiu de la baixa i qualsevol informació rellevant.')
                        ])
                        ->action(function (Empleat $record, array $data): void {
                            try {
                                $record->donarBaixa($data['observacions_baixa']);
                                
                                Notification::make()
                                    ->title('Empleat donat de baixa')
                                    ->body("S'ha processat la baixa de {$record->nom_complet} i s'han creat les tasques d'offboarding.")
                                    ->success()
                                    ->send();
                                    
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error en processar la baixa')
                                    ->body('Hi ha hagut un error processar la baixa: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                        
                    Action::make('reactivar')
                        ->label('Reactivar')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->visible(fn (Empleat $record): bool => $record->estat === 'baixa')
                        ->requiresConfirmation()
                        ->action(function (Empleat $record): void {
                            $record->update([
                                'estat' => 'actiu',
                                'data_baixa' => null
                            ]);
                            
                            Notification::make()
                                ->title('Empleat reactivat')
                                ->body("{$record->nom_complet} ha estat reactivat correctament.")
                                ->success()
                                ->send();
                        }),
                        
                    Action::make('veure_checklists')
                        ->label('Veure Checklists')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('info')
                        ->url(fn (Empleat $record): string => 
                            route('filament.admin.resources.checklist-instances.index', [
                                'tableFilters[empleat_id][value]' => $record->id
                            ])
                        )
                        ->visible(fn (Empleat $record): bool => $record->checklists()->exists()),
                        
                    Action::make('veure_solicituds')
                        ->label('Veure Sol·licituds')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->url(fn (Empleat $record): string => 
                            route('filament.admin.resources.solicitud-acces.index', [
                                'tableFilters[empleat_destinatari_id][value]' => $record->id
                            ])
                        )
                        ->visible(fn (Empleat $record): bool => $record->solicitudsAcces()->exists()),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Atenció: Aquesta acció eliminarà permanentment els registres seleccionats i totes les seves dades relacionades.'),
                        
                    Tables\Actions\BulkAction::make('exportar')
                        ->label('Exportar Seleccionats')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // TODO: Implementar exportació
                            Notification::make()
                                ->title('Exportació en desenvolupament')
                                ->body('Aquesta funcionalitat s\'implementarà en la següent fase.')
                                ->info()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
        $count = static::getNavigationBadge();
        
        if ($count > 100) {
            return 'success';
        } elseif ($count > 50) {
            return 'warning';
        }
        
        return 'primary';
    }

    // Scopes per autoritzacions
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        
        // Si l'usuari és gestor, només veu empleats dels seus departaments
        if ($user && $user->rol_principal === 'gestor') {
            $departamentIds = $user->departamentsGestionats()->pluck('departaments.id');
            $query->whereIn('departament_id', $departamentIds);
        }
        
        return $query;
    }
}