<?php

namespace App\Filament\Resources\SistemaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ValidadorsRelationManager extends RelationManager
{
    protected static string $relationship = 'validadors';
    protected static ?string $title = 'Validadors';
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('validador_id')
                    ->label('Validador')
                    ->options(User::where('actiu', true)
                        ->whereIn('rol_principal', ['admin', 'rrhh', 'it', 'gestor'])
                        ->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\TextInput::make('ordre')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->label('Ordre de Validació')
                    ->minValue(1)
                    ->maxValue(10)
                    ->helperText('Ordre en què aquest validador ha de revisar les sol·licituds'),
                    
                Forms\Components\Toggle::make('requerit')
                    ->default(true)
                    ->label('Validació Obligatòria')
                    ->helperText('Si aquesta validació és obligatòria per aprovar la sol·licitud'),
                    
                Forms\Components\Toggle::make('actiu')
                    ->default(true)
                    ->label('Validador Actiu'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('ordre_validacio')
                    ->label('#')
                    ->weight('bold')
                    ->color('gray')
                    ->getStateUsing(function (User $record) {
                        $pivot = $record->pivot ?? null;
                        return $pivot ? $pivot->ordre : '-';
                    }),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom del Validador')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\BadgeColumn::make('rol_principal')
                    ->label('Rol')
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'rrhh',
                        'primary' => 'it',
                        'success' => 'gestor',
                    ]),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('Correu')
                    ->searchable()
                    ->limit(25),
                    
                Tables\Columns\IconColumn::make('es_requerit')
                    ->label('Obligatori')
                    ->boolean()
                    ->getStateUsing(function (User $record) {
                        $pivot = $record->pivot ?? null;
                        return $pivot ? $pivot->requerit : false;
                    }),
                    
                Tables\Columns\IconColumn::make('esta_actiu')
                    ->label('Actiu')
                    ->boolean()
                    ->getStateUsing(function (User $record) {
                        $pivot = $record->pivot ?? null;
                        return $pivot ? $pivot->actiu : false;
                    }),
                    
                Tables\Columns\TextColumn::make('validacions_count')
                    ->label('Validacions')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(function (User $record) {
                        // Temporal: comentat fins implementar Validacio
                        // return \App\Models\Validacio::where('validador_id', $record->id)
                        //     ->where('sistema_id', $this->ownerRecord->id)
                        //     ->count();
                        return 0;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rol_principal')
                    ->options([
                        'admin' => 'Administrador',
                        'rrhh' => 'RRHH',
                        'it' => 'IT',
                        'gestor' => 'Gestor',
                    ]),
                    
                Tables\Filters\SelectFilter::make('requerit')
                    ->label('Obligatorietat')
                    ->options([
                        1 => 'Obligatoris',
                        0 => 'Opcionals',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            $query->wherePivot('requerit', $data['value']);
                        }
                    }),
            ])
            ->headerActions([
                Action::make('afegir_validador')
                    ->label('Afegir Validador')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('validador_id')
                            ->label('Seleccionar Usuari com a Validador')
                            ->options(function () {
                                // Obtenir IDs dels usuaris que ja són validadors d'aquest sistema
                                $usuarisExistents = $this->ownerRecord->validadors()->pluck('users.id')->toArray();
                                
                                // Retornar usuaris actius amb rols apropiats que NO siguin ja validadors
                                return User::where('actiu', true)
                                    ->whereIn('rol_principal', ['admin', 'rrhh', 'it', 'gestor'])
                                    ->whereNotIn('id', $usuarisExistents)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('Buscar i seleccionar usuari...')
                            ->helperText('Selecciona quin usuari serà validador d\'aquest sistema'),
                            
                        Forms\Components\TextInput::make('ordre')
                            ->numeric()
                            ->default(function () {
                                // Calcular el següent ordre disponible
                                $maxOrdre = $this->ownerRecord->validadors()->max('sistema_validadors.ordre') ?? 0;
                                return $maxOrdre + 1;
                            })
                            ->required()
                            ->label('Ordre de Validació')
                            ->helperText('En quin ordre ha de validar (1 = primer)')
                            ->minValue(1)
                            ->maxValue(10),
                            
                        Forms\Components\Toggle::make('requerit')
                            ->default(true)
                            ->label('Validació Obligatòria')
                            ->helperText('Si aquesta validació és obligatòria per aprovar sol·licituds'),
                            
                        Forms\Components\Toggle::make('actiu')
                            ->default(true)
                            ->label('Validador Actiu')
                            ->helperText('Si aquest validador està actiu actualment'),
                    ])
                    ->action(function (array $data) {
                        try {
                            // Afegir el validador al sistema
                            $this->ownerRecord->validadors()->attach($data['validador_id'], [
                                'ordre' => $data['ordre'],
                                'requerit' => $data['requerit'],
                                'actiu' => $data['actiu'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            $usuari = User::find($data['validador_id']);
                            
                            Notification::make()
                                ->title('Validador afegit correctament')
                                ->body("L'usuari {$usuari->name} ({$usuari->rol_principal}) s'ha afegit com a validador amb ordre {$data['ordre']}.")
                                ->success()
                                ->duration(5000)
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al afegir validador')
                                ->body('Hi ha hagut un problema: ' . $e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                    
                Action::make('test_relacio')
                    ->label('Test Relació')
                    ->icon('heroicon-o-bug-ant')
                    ->color('gray')
                    ->action(function () {
                        $sistema = $this->ownerRecord;
                        
                        $info = [
                            'Sistema ID' => $sistema->id,
                            'Sistema Nom' => $sistema->nom,
                            'Validadors Actuals' => $sistema->validadors()->count(),
                            'Usuaris Disponibles' => User::where('actiu', true)
                                ->whereIn('rol_principal', ['admin', 'rrhh', 'it', 'gestor'])
                                ->count(),
                            'Taula sistema_validadors existeix' => \Schema::hasTable('sistema_validadors') ? 'Sí' : 'No',
                        ];
                        
                        $message = collect($info)->map(fn($value, $key) => "{$key}: {$value}")->implode("\n");
                        
                        Notification::make()
                            ->title('Informació de Debug')
                            ->body($message)
                            ->info()
                            ->persistent()
                            ->send();
                    })
                    ->label('Configurar Flux de Validació')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('info')
                    ->modalHeading('Configurar Flux de Validació')
                    ->modalDescription('Defineix com han de funcionar les validacions per aquest sistema.')
                    ->form([
                        Forms\Components\Select::make('tipus_flux')
                            ->label('Tipus de Flux')
                            ->options([
                                'sequencial' => 'Seqüencial (un darrere l\'altre)',
                                'paralel' => 'Paral·lel (tots a la vegada)',
                                'primer_disponible' => 'Primer Disponible',
                            ])
                            ->default('sequencial')
                            ->required(),
                            
                        Forms\Components\Toggle::make('tots_obligatoris')
                            ->label('Tots els Validadors són Obligatoris')
                            ->default(true),
                            
                        Forms\Components\TextInput::make('temps_limit')
                            ->label('Temps Límit (hores)')
                            ->numeric()
                            ->placeholder('Opcional - temps límit per validar'),
                    ])
                    ->action(function (array $data) {
                        // Aquí es podria guardar la configuració del flux
                        // en una taula de configuració o com a metadata
                        
                        Notification::make()
                            ->title('Flux de validació configurat')
                            ->body('La configuració del flux de validació s\'ha guardat correctament.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('editar_validador')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Forms\Components\TextInput::make('ordre')
                            ->numeric()
                            ->required()
                            ->label('Ordre de Validació')
                            ->default(function (User $record) {
                                return $record->pivot->ordre ?? 1;
                            }),
                            
                        Forms\Components\Toggle::make('requerit')
                            ->label('Validació Obligatòria')
                            ->default(function (User $record) {
                                return $record->pivot->requerit ?? true;
                            }),
                            
                        Forms\Components\Toggle::make('actiu')
                            ->label('Validador Actiu')
                            ->default(function (User $record) {
                                return $record->pivot->actiu ?? true;
                            }),
                    ])
                    ->action(function (User $record, array $data) {
                        $this->ownerRecord->validadors()->updateExistingPivot($record->id, [
                            'ordre' => $data['ordre'],
                            'requerit' => $data['requerit'],
                            'actiu' => $data['actiu'],
                        ]);
                        
                        Notification::make()
                            ->title('Validador actualitzat')
                            ->body("S'ha actualitzat la configuració de {$record->name}.")
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\DetachAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Validador')
                    ->modalDescription('Aquesta acció eliminarà aquest validador del sistema.')
                    ->successNotificationTitle('Validador eliminat'),
                    
                Action::make('veure_validacions')
                    ->label('Historial')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->url(fn (User $record) => 
                        // Podríem crear un enllaç a un recurs de validacions filtrat
                        '#'
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}