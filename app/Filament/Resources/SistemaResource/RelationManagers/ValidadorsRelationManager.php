<?php

namespace App\Filament\Resources\SistemaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use App\Models\User;
use App\Models\Departament;
use App\Models\SistemaValidador;
use Illuminate\Database\Eloquent\Builder;

class ValidadorsRelationManager extends RelationManager
{
    protected static string $relationship = 'sistemaValidadors';
    
    protected static ?string $title = 'Validadors del Sistema';
    
    protected static ?string $modelLabel = 'Validador';
    
    protected static ?string $pluralModelLabel = 'Validadors';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('tipus_validador')
                            ->label('Tipus de Validador')
                            ->options([
                                'usuari_especific' => 'Usuari Específic',
                                'gestor_departament' => 'Gestors de Departament'
                            ])
                            ->required()
                            ->default('usuari_especific')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Netejar camps quan canvia el tipus
                                if ($state === 'usuari_especific') {
                                    $set('departament_validador_id', null);
                                } else {
                                    $set('validador_id', null);
                                }
                            }),
                    ])
                    ->columnSpanFull(),
                
                // USUARI ESPECÍFIC
                Grid::make(1)
                    ->schema([
                        Select::make('validador_id')
                            ->label('Usuari Validador')
                            ->relationship('validador', 'name')
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->email}) - {$record->rol_principal}")
                            ->optionsLimit(50)
                            ->preload()
                            ->visible(fn ($get) => $get('tipus_validador') === 'usuari_especific')
                            ->required(fn ($get) => $get('tipus_validador') === 'usuari_especific')
                            ->helperText('Usuari específic que pot validar sol·licituds d\'aquest sistema'),
                    ])
                    ->visible(fn ($get) => $get('tipus_validador') === 'usuari_especific'),
                
                // GESTORS DE DEPARTAMENT - CORRECCIÓ AQUÍ
                Grid::make(1)
                    ->schema([
                        Select::make('departament_validador_id')
                            ->label('Departament')
                            ->relationship('departamentValidador', 'nom')
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn (Departament $record) => 
                                "{$record->nom} " . ($record->gestor ? "({$record->gestor->name})" : "(sense gestor)")
                            )
                            ->optionsLimit(50)
                            ->preload()
                            ->visible(fn ($get) => $get('tipus_validador') === 'gestor_departament')
                            ->required(fn ($get) => $get('tipus_validador') === 'gestor_departament')
                            ->helperText('Tots els gestors d\'aquest departament podran validar sol·licituds'),
                    ])
                    ->visible(fn ($get) => $get('tipus_validador') === 'gestor_departament'),
                
                Grid::make(3)
                    ->schema([
                        TextInput::make('ordre')
                            ->label('Ordre de Validació')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(function () {
                                $maxOrdre = $this->getOwnerRecord()
                                    ->sistemaValidadors()
                                    ->max('ordre');
                                return ($maxOrdre ?? 0) + 1;
                            })
                            ->helperText('Ordre en què s\'han de validar (1 = primer)'),
                        
                        Toggle::make('requerit')
                            ->label('Validació Obligatòria')
                            ->default(true)
                            ->helperText('Si és obligatòria per aprovar la sol·licitud'),
                        
                        Toggle::make('actiu')
                            ->label('Validador Actiu')
                            ->default(true)
                            ->helperText('Disponible per processar sol·licituds'),
                    ]),
                
                // Vista prèvia del validador - MILLORADA
                Forms\Components\Placeholder::make('preview')
                    ->label('Vista Prèvia de la Configuració')
                    ->content(function ($get) {
                        $tipus = $get('tipus_validador');
                        $validadorId = $get('validador_id');
                        $departamentId = $get('departament_validador_id');
                        $ordre = $get('ordre') ?? 1;
                        $requerit = $get('requerit') ?? true;
                        
                        if ($tipus === 'usuari_especific' && $validadorId) {
                            $user = User::find($validadorId);
                            $requirit_text = $requerit ? 'OBLIGATÒRIA' : 'OPCIONAL';
                            return "✅ **{$user?->name}** validarà sol·licituds (Ordre: {$ordre}, Validació: {$requirit_text})";
                        }
                        
                        if ($tipus === 'gestor_departament' && $departamentId) {
                            $departament = Departament::find($departamentId);
                            $gestors_count = $departament?->gestors()->count() ?? 0;
                            $requirit_text = $requerit ? 'OBLIGATÒRIA' : 'OPCIONAL';
                            return "✅ **Gestors del departament '{$departament?->nom}'** ({$gestors_count} gestor(s)) validaran sol·licituds (Ordre: {$ordre}, Validació: {$requirit_text})";
                        }
                        
                        return '⚠️ Configura tots els camps per veure la vista prèvia';
                    })
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('ordre')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->width('80px'),
                
                BadgeColumn::make('tipus_validador')
                    ->label('Tipus')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'usuari_especific' => 'Usuari',
                        'gestor_departament' => 'Gestors Dept.',
                        default => $state
                    })
                    ->colors([
                        'primary' => 'usuari_especific',
                        'success' => 'gestor_departament',
                    ])
                    ->icons([
                        'heroicon-o-user' => 'usuari_especific',
                        'heroicon-o-user-group' => 'gestor_departament',
                    ]),
                
                // COLUMNA MILLORADA PER MOSTRAR VALIDADOR O DEPARTAMENT
                TextColumn::make('nom_validador')
                    ->label('Validador(s)')
                    ->getStateUsing(function (SistemaValidador $record): string {
                        if ($record->tipus_validador === 'usuari_especific') {
                            return $record->validador?->name ?? 'Usuari eliminat';
                        }
                        
                        if ($record->tipus_validador === 'gestor_departament') {
                            $departament = $record->departamentValidador;
                            if ($departament) {
                                $gestors_count = $departament->gestors()->count();
                                return "{$departament->nom} ({$gestors_count} gestor(s))";
                            }
                            return 'Departament no configurat';
                        }
                        
                        return 'Configuració no vàlida';
                    })
                    ->searchable()
                    ->wrap(),
                
                TextColumn::make('detalls_validador')
                    ->label('Detalls')
                    ->getStateUsing(function (SistemaValidador $record): string {
                        if ($record->tipus_validador === 'usuari_especific') {
                            return $record->validador?->email ?? '';
                        }
                        
                        if ($record->tipus_validador === 'gestor_departament') {
                            $departament = $record->departamentValidador;
                            if ($departament && $departament->gestor) {
                                return "Principal: {$departament->gestor->name}";
                            }
                            return 'Sense gestor principal';
                        }
                        
                        return '';
                    })
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                IconColumn::make('requerit')
                    ->label('Obligatori')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),
                
                IconColumn::make('actiu')
                    ->boolean()
                    ->sortable(),
                
                // COLUMNA ESTAT OPERATIU
                BadgeColumn::make('estat_operatiu')
                    ->label('Estat')
                    ->getStateUsing(function (SistemaValidador $record): string {
                        if (!$record->actiu) {
                            return 'inactiu';
                        }
                        
                        if ($record->tipus_validador === 'usuari_especific') {
                            return $record->validador && $record->validador->actiu ? 'operatiu' : 'usuari_inactiu';
                        }
                        
                        if ($record->tipus_validador === 'gestor_departament') {
                            $departament = $record->departamentValidador;
                            if (!$departament) return 'error';
                            
                            $gestors_actius = $departament->gestors()->where('users.actiu', true)->count();
                            return $gestors_actius > 0 ? 'operatiu' : 'sense_gestors';
                        }
                        
                        return 'error';
                    })
                    ->colors([
                        'success' => 'operatiu',
                        'warning' => 'sense_gestors',
                        'danger' => ['usuari_inactiu', 'error'],
                        'gray' => 'inactiu',
                    ]),
                
                TextColumn::make('created_at')
                    ->label('Afegit')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipus_validador')
                    ->label('Tipus de Validador')
                    ->options([
                        'usuari_especific' => 'Usuari Específic',
                        'gestor_departament' => 'Gestors Departament'
                    ]),
                
                Filter::make('requerits')
                    ->label('Només obligatoris')
                    ->query(fn (Builder $query): Builder => $query->where('requerit', true)),
                
                Filter::make('actius')
                    ->label('Només actius')
                    ->query(fn (Builder $query): Builder => $query->where('actiu', true))
                    ->default(),
                
                Filter::make('operatius')
                    ->label('Només operatius')
                    ->query(function (Builder $query): Builder {
                        return $query->where('actiu', true)
                                   ->where(function ($q) {
                                       $q->where('tipus_validador', 'usuari_especific')
                                         ->whereHas('validador', fn ($sq) => $sq->where('actiu', true))
                                         ->orWhere(function ($sq) {
                                             $sq->where('tipus_validador', 'gestor_departament')
                                                ->whereHas('departamentValidador.gestors', 
                                                          fn ($ssq) => $ssq->where('users.actiu', true));
                                         });
                                   });
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Afegir Validador')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Configurar Nou Validador')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['sistema_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
                
                Action::make('assistt_configuracio')
                    ->label('Assistent de Configuració')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->modalHeading('Assistent de Configuració de Validadors')
                    ->modalDescription('Configuració ràpida dels validadors més comuns')
                    ->form([
                        Select::make('tipus_config')
                            ->label('Tipus de Configuració')
                            ->options([
                                'admin_rrhh' => 'Admin + RRHH (usuaris específics)',
                                'gestor_dept_empleat' => 'Gestor del departament de l\'empleat',
                                'gestor_dept_especific' => 'Gestor d\'un departament específic',
                                'mix_admin_gestor' => 'Admin + Gestor departament empleat',
                            ])
                            ->required()
                            ->live(),
                            
                        Select::make('departament_especific')
                            ->label('Departament Específic')
                            ->relationship('departaments', 'nom')
                            ->visible(fn ($get) => $get('tipus_config') === 'gestor_dept_especific')
                            ->required(fn ($get) => $get('tipus_config') === 'gestor_dept_especific'),
                    ])
                    ->action(function (array $data) {
                        $sistema = $this->getOwnerRecord();
                        
                        // Esborrar validadors existents
                        $sistema->sistemaValidadors()->delete();
                        
                        switch ($data['tipus_config']) {
                            case 'admin_rrhh':
                                // Admin primer
                                $admin = User::where('rol_principal', 'admin')->where('actiu', true)->first();
                                if ($admin) {
                                    $sistema->sistemaValidadors()->create([
                                        'validador_id' => $admin->id,
                                        'tipus_validador' => 'usuari_especific',
                                        'ordre' => 1,
                                        'requerit' => true,
                                        'actiu' => true,
                                    ]);
                                }
                                
                                // RRHH segon
                                $rrhh = User::where('rol_principal', 'rrhh')->where('actiu', true)->first();
                                if ($rrhh) {
                                    $sistema->sistemaValidadors()->create([
                                        'validador_id' => $rrhh->id,
                                        'tipus_validador' => 'usuari_especific',
                                        'ordre' => 2,
                                        'requerit' => true,
                                        'actiu' => true,
                                    ]);
                                }
                                break;
                                
                            case 'gestor_dept_empleat':
                                // Sense departament específic = gestor del departament de l'empleat
                                $sistema->sistemaValidadors()->create([
                                    'validador_id' => null,
                                    'departament_validador_id' => null, // Null = departament de l'empleat
                                    'tipus_validador' => 'gestor_departament',
                                    'ordre' => 1,
                                    'requerit' => true,
                                    'actiu' => true,
                                ]);
                                break;
                                
                            case 'gestor_dept_especific':
                                if ($data['departament_especific']) {
                                    $sistema->sistemaValidadors()->create([
                                        'validador_id' => null,
                                        'departament_validador_id' => $data['departament_especific'],
                                        'tipus_validador' => 'gestor_departament',
                                        'ordre' => 1,
                                        'requerit' => true,
                                        'actiu' => true,
                                    ]);
                                }
                                break;
                                
                            case 'mix_admin_gestor':
                                // Admin primer
                                $admin = User::where('rol_principal', 'admin')->where('actiu', true)->first();
                                if ($admin) {
                                    $sistema->sistemaValidadors()->create([
                                        'validador_id' => $admin->id,
                                        'tipus_validador' => 'usuari_especific',
                                        'ordre' => 1,
                                        'requerit' => true,
                                        'actiu' => true,
                                    ]);
                                }
                                
                                // Gestor departament empleat segon
                                $sistema->sistemaValidadors()->create([
                                    'validador_id' => null,
                                    'departament_validador_id' => null,
                                    'tipus_validador' => 'gestor_departament',
                                    'ordre' => 2,
                                    'requerit' => true,
                                    'actiu' => true,
                                ]);
                                break;
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Configuració aplicada')
                            ->body('Els validadors s\'han configurat segons la plantilla seleccionada.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Editar Configuració del Validador'),
                
                Action::make('provar_configuracio')
                    ->label('Provar')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->action(function (SistemaValidador $record) {
                        $detalls = $record->getDetallarValidadors();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Prova de Configuració')
                            ->body("Tipus: {$detalls['descripcio']}\nValidadors actius: {$detalls['total']}")
                            ->success()
                            ->send();
                    }),
                
                Action::make('move_up')
                    ->label('Pujar')
                    ->icon('heroicon-o-arrow-up')
                    ->color('info')
                    ->action(function (SistemaValidador $record) {
                        $previousValidador = $this->getOwnerRecord()
                            ->sistemaValidadors()
                            ->where('ordre', '<', $record->ordre)
                            ->orderBy('ordre', 'desc')
                            ->first();
                        
                        if ($previousValidador) {
                            $tempOrdre = $record->ordre;
                            $record->update(['ordre' => $previousValidador->ordre]);
                            $previousValidador->update(['ordre' => $tempOrdre]);
                        }
                    })
                    ->visible(function (SistemaValidador $record): bool {
                        return $this->getOwnerRecord()
                            ->sistemaValidadors()
                            ->where('ordre', '<', $record->ordre)
                            ->exists();
                    }),
                
                Action::make('move_down')
                    ->label('Baixar')
                    ->icon('heroicon-o-arrow-down')
                    ->color('info')
                    ->action(function (SistemaValidador $record) {
                        $nextValidador = $this->getOwnerRecord()
                            ->sistemaValidadors()
                            ->where('ordre', '>', $record->ordre)
                            ->orderBy('ordre', 'asc')
                            ->first();
                        
                        if ($nextValidador) {
                            $tempOrdre = $record->ordre;
                            $record->update(['ordre' => $nextValidador->ordre]);
                            $nextValidador->update(['ordre' => $tempOrdre]);
                        }
                    })
                    ->visible(function (SistemaValidador $record): bool {
                        return $this->getOwnerRecord()
                            ->sistemaValidadors()
                            ->where('ordre', '>', $record->ordre)
                            ->exists();
                    }),
                
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['actiu' => true]);
                            }
                        }),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['actiu' => false]);
                            }
                        }),
                    
                    Tables\Actions\BulkAction::make('mark_required')
                        ->label('Marcar com obligatoris')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['requerit' => true]);
                            }
                        }),
                    
                    Tables\Actions\BulkAction::make('mark_optional')
                        ->label('Marcar com opcionals')
                        ->icon('heroicon-o-minus')
                        ->color('gray')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['requerit' => false]);
                            }
                        }),
                ])
            ])
            ->defaultSort('ordre')
            ->reorderable('ordre')
            ->paginated(false);
    }
}