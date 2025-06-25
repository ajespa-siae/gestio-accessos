<?php
// app/Filament/Resources/SistemaResource.php - VERSIÓN CORREGIDA PARA POSTGRESQL

namespace App\Filament\Resources;

use App\Filament\Resources\SistemaResource\Pages;
use App\Filament\Resources\SistemaResource\RelationManagers;
use App\Models\Sistema;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SistemaResource extends Resource
{
    protected static ?string $model = Sistema::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'Sistemes';
    
    protected static ?string $modelLabel = 'Sistema';
    
    protected static ?string $pluralModelLabel = 'Sistemes';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informació Bàsica')
                    ->schema([
                        TextInput::make('nom')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label('Nom del Sistema')
                            ->helperText('Nom identificatiu del sistema (ex: Gestor d\'Expedients, ERP, etc.)'),
                            
                        Textarea::make('descripcio')
                            ->maxLength(1000)
                            ->label('Descripció')
                            ->helperText('Descripció detallada del sistema i la seva funcionalitat')
                            ->columnSpanFull(),
                            
                        Toggle::make('actiu')
                            ->default(true)
                            ->label('Sistema Actiu')
                            ->helperText('Els sistemes inactius no apareixeran en noves sol·licituds'),
                    ])
                    ->columns(2),
                    
                Section::make('Configuració de Validadors')
                    ->schema([
                        Repeater::make('configuracio_validadors_temp')
                            ->label('Configuració de Validadors')
                            ->schema([
                                Select::make('tipus_validador')
                                    ->label('Tipus de Validador')
                                    ->options([
                                        'usuari_especific' => 'Usuari Específic',
                                        'rol' => 'Per Rol',
                                        'gestor_departament' => 'Gestor del Departament',
                                        'qualsevol_gestor' => 'Qualsevol Gestor'
                                    ])
                                    ->required()
                                    ->reactive(),
                                    
                                Select::make('usuari_id')
                                    ->label('Usuari Específic')
                                    ->options(User::where('actiu', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->visible(fn ($get) => $get('tipus_validador') === 'usuari_especific')
                                    ->required(fn ($get) => $get('tipus_validador') === 'usuari_especific'),
                                    
                                Select::make('rol')
                                    ->label('Rol Requerit')
                                    ->options([
                                        'admin' => 'Administrador',
                                        'rrhh' => 'RRHH',
                                        'it' => 'IT',
                                        'gestor' => 'Gestor'
                                    ])
                                    ->visible(fn ($get) => $get('tipus_validador') === 'rol')
                                    ->required(fn ($get) => $get('tipus_validador') === 'rol'),
                                    
                                TextInput::make('ordre')
                                    ->label('Ordre de Validació')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(10)
                                    ->helperText('Ordre en què s\'ha de validar (1 = primer)'),
                                    
                                Toggle::make('obligatori')
                                    ->label('Validació Obligatòria')
                                    ->default(true)
                                    ->helperText('Si no és obligatori, la validació és opcional'),
                            ])
                            ->columnSpanFull()
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                if (!isset($state['tipus_validador'])) return null;
                                
                                $label = match($state['tipus_validador']) {
                                    'usuari_especific' => 'Usuari: ' . (User::find($state['usuari_id'])?->name ?? 'No seleccionat'),
                                    'rol' => 'Rol: ' . ($state['rol'] ?? 'No seleccionat'),
                                    'gestor_departament' => 'Gestor del Departament',
                                    'qualsevol_gestor' => 'Qualsevol Gestor',
                                    default => 'Configuració'
                                };
                                
                                $ordre = isset($state['ordre']) ? " (Ordre: {$state['ordre']})" : '';
                                return $label . $ordre;
                            })
                            ->addActionLabel('Afegir Validador')
                            ->helperText('Definiu qui ha de validar les sol·licituds d\'accés a aquest sistema'),
                    ])
                    ->collapsible(),
                    
                Section::make('Departaments Autoritzats')
                    ->schema([
                        CheckboxList::make('departaments_autoritzats')
                            ->label('Departaments que poden sol·licitar accés')
                            ->options(function() {
                                // Evitar DISTINCT seleccionando columnas específicas
                                return Departament::query()
                                    ->select(['id', 'nom'])
                                    ->where('actiu', true)
                                    ->orderBy('nom')
                                    ->get()
                                    ->pluck('nom', 'id')
                                    ->toArray();
                            })
                            ->columns(2)
                            ->columnSpanFull()
                            ->helperText('Seleccioneu els departaments que poden sol·licitar accés a aquest sistema. Si no se\'n selecciona cap, tots els departaments podran sol·licitar accés.'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')
                    ->searchable()
                    ->label('Nom del Sistema')
                    ->sortable(),
                    
                TextColumn::make('descripcio')
                    ->limit(50)
                    ->label('Descripció')
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(),
                    
                IconColumn::make('actiu')
                    ->boolean()
                    ->label('Actiu'),
                    
                // Eliminamos las columnas de conteo para evitar problemas de cardinalidad en PostgreSQL
                    
                TextColumn::make('solicituds_total')
                    ->label('Sol·licituds Total')
                    ->getStateUsing(function ($record) {
                        return $record->solicituds()->count();
                    })
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                    
                TextColumn::make('solicituds_pendents')
                    ->label('Pendents')
                    ->getStateUsing(function ($record) {
                        return $record->solicituds()
                            ->whereHas('solicitud', fn ($q) => $q->whereIn('estat', ['pendent', 'validant']))
                            ->count();
                    })
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->toggleable(),
                    
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->label('Creat')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Filter::make('actius')
                    ->label('Només actius')
                    ->query(fn (Builder $query): Builder => $query->where('actiu', true))
                    ->default(),
                    
                Filter::make('amb_nivells')
                    ->label('Amb nivells d\'accés')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('nivellsAcces', fn ($q) => $q->where('actiu', true))
                    ),
                    
                // FILTRE SIMPLIFICAT PER EVITAR PROBLEMES POSTGRESQL
                Filter::make('sense_validadors')
                    ->label('Sense validadors configurats')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNull('configuracio_validadors')
                    ),
                    
                SelectFilter::make('departament')
                    ->label('Per departament')
                    ->relationship('departaments', 'nom')
                    ->searchable()
                    ->preload(),
                    
                Filter::make('solicituds_pendents')
                    ->label('Amb sol·licituds pendents')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('solicituds', function ($q) {
                            $q->whereHas('solicitud', fn ($sq) => $sq->whereIn('estat', ['pendent', 'validant']));
                        });
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Action::make('configurar_nivells')
                        ->label('Configurar Nivells')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->color('info')
                        ->url(fn (Sistema $record): string => 
                            static::getUrl('edit', ['record' => $record]) . '#nivells-acces'
                        ),
                        
                    Action::make('veure_solicituds')
                        ->label('Veure Sol·licituds')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->visible(fn (Sistema $record): bool => 
                            $record->solicituds()->exists()
                        )
                        ->url(fn (Sistema $record): string => 
                            route('filament.admin.resources.solicitud-sistemes.index', [
                                'tableFilters[sistema_id][value]' => $record->id
                            ])
                        ),
                        
                    Action::make('duplicar')
                        ->label('Duplicar Sistema')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->form([
                            TextInput::make('nou_nom')
                                ->label('Nom del nou sistema')
                                ->required()
                                ->helperText('El nou sistema es crearà amb la mateixa configuració'),
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
                            
                            Notification::make()
                                ->title('Sistema duplicat correctament')
                                ->body("S'ha creat el sistema '{$data['nou_nom']}' amb la mateixa configuració.")
                                ->success()
                                ->send();
                        }),
                        
                    Action::make('activar_desactivar')
                        ->label(fn (Sistema $record) => $record->actiu ? 'Desactivar' : 'Activar')
                        ->icon(fn (Sistema $record) => $record->actiu ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn (Sistema $record) => $record->actiu ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->modalDescription(fn (Sistema $record) => 
                            $record->actiu 
                                ? 'Desactivar el sistema farà que no aparegui en noves sol·licituds d\'accés.'
                                : 'Activar el sistema farà que torni a aparèixer en les sol·licituds d\'accés.'
                        )
                        ->action(function (Sistema $record): void {
                            $record->update(['actiu' => !$record->actiu]);
                            
                            $estat = $record->actiu ? 'activat' : 'desactivat';
                            Notification::make()
                                ->title("Sistema {$estat}")
                                ->success()
                                ->send();
                        }),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Atenció: Aquesta acció eliminarà permanentment els sistemes seleccionats i totes les seves dades relacionades.'),
                        
                    Tables\Actions\BulkAction::make('activar')
                        ->label('Activar Seleccionats')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['actiu' => true]);
                            
                            Notification::make()
                                ->title('Sistemes activats')
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\BulkAction::make('desactivar')
                        ->label('Desactivar Seleccionats')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['actiu' => false]);
                            
                            Notification::make()
                                ->title('Sistemes desactivats')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('nom')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\NivellsAccesRelationManager::class,
            //RelationManagers\DepartamentsRelationManager::class,
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
        $inactius = static::getModel()::where('actiu', false)->count();
        
        if ($inactius > 0) {
            return 'warning';
        }
        
        return 'success';
    }

    // Scopes per autoritzacions
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        
        // Si l'usuari és gestor, només veu sistemes dels seus departaments
        if ($user && $user->rol_principal === 'gestor') {
            $departamentIds = $user->departamentsGestionats()->pluck('departaments.id');
            $query->whereHas('departaments', function ($q) use ($departamentIds) {
                $q->whereIn('departament_id', $departamentIds);
            });
        }
        
        return $query;
    }
}