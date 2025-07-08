<?php

namespace App\Filament\Resources\DepartamentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Models\Departament;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Actions\DetachBulkAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\DB;

class GestorsRelationManager extends RelationManager
{
    protected static string $relationship = 'gestors';

    protected static ?string $title = 'Gestors del Departament';

    protected static ?string $modelLabel = 'Gestor';

    protected static ?string $pluralModelLabel = 'Gestors';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Usuari')
                    ->options(function () {
                        return User::where('actiu', true)
                                  ->whereIn('rol_principal', ['admin', 'rrhh', 'gestor'])
                                  ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->getOptionLabelFromRecordUsing(fn (User $record) => "{$record->name} ({$record->email})"),
                    
                Toggle::make('gestor_principal')
                    ->label('Gestor Principal')
                    ->helperText('Només pot haver-hi un gestor principal per departament.')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record): string => $record->email),
                    
                TextColumn::make('rol_principal')
                    ->label('Rol')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'rrhh' => 'warning', 
                        'it' => 'info',
                        'gestor' => 'success',
                        default => 'gray',
                    }),
                    
                IconColumn::make('es_principal')
                    ->label('Principal')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->getStateUsing(function (User $record): bool {
                        return DB::table('departament_gestors')
                            ->where('departament_id', $this->getOwnerRecord()->id)
                            ->where('user_id', $record->id)
                            ->where('gestor_principal', true)
                            ->exists();
                    }),
                    
                TextColumn::make('altres_departaments')
                    ->label('Altres Departaments')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(function (User $record): int {
                        return DB::table('departament_gestors')
                            ->where('user_id', $record->id)
                            ->where('departament_id', '!=', $this->getOwnerRecord()->id)
                            ->count();
                    }),
                    
                IconColumn::make('actiu')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                TextColumn::make('data_afegit')
                    ->label('Afegit')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->getStateUsing(function (User $record): ?string {
                        $gestorData = DB::table('departament_gestors')
                            ->where('departament_id', $this->getOwnerRecord()->id)
                            ->where('user_id', $record->id)
                            ->first();
                        
                        return $gestorData?->created_at;
                    }),
            ])
            ->filters([
                Filter::make('principal')
                    ->label('Només Gestors Principals')
                    ->query(function (Builder $query): Builder {
                        $departamentId = $this->getOwnerRecord()->id;
                        return $query->whereExists(function ($subquery) use ($departamentId) {
                            $subquery->select(DB::raw(1))
                                   ->from('departament_gestors')
                                   ->whereColumn('departament_gestors.user_id', 'users.id')
                                   ->where('departament_gestors.departament_id', $departamentId)
                                   ->where('departament_gestors.gestor_principal', true);
                        });
                    }),
                    
                Filter::make('actius')
                    ->label('Només Usuaris Actius')
                    ->query(fn (Builder $query): Builder => $query->where('users.actiu', true))
                    ->default(),
            ])
            ->headerActions([
                Action::make('afegir_gestor')
                    ->label('Afegir Gestor')
                    ->modalHeading('Afegir Gestor al Departament')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('user_id')
                            ->label('Usuari')
                            ->options(function () {
                                // Obtener usuarios activos con rol gestor que no están ya asignados a este departamento
                                $departamentId = $this->getOwnerRecord()->id;
                                
                                // Obtener usuarios con rol gestor usando RBAC
                                $usuariosGestores = User::where('actiu', true)
                                    ->whereHas('roles', function ($query) {
                                        $query->where('name', 'gestor');
                                    })
                                    ->whereNotExists(function ($query) use ($departamentId) {
                                        $query->select(DB::raw(1))
                                            ->from('departament_gestors')
                                            ->whereColumn('departament_gestors.user_id', 'users.id')
                                            ->where('departament_gestors.departament_id', $departamentId);
                                    })
                                    ->get();
                                
                                // Formatear los nombres para mostrar también el email
                                $options = [];
                                foreach ($usuariosGestores as $usuario) {
                                    $options[$usuario->id] = "{$usuario->name} ({$usuario->email})";
                                }
                                
                                return $options;
                            })
                            ->searchable()
                            ->required(),
                            
                        Toggle::make('gestor_principal')
                            ->label('Gestor Principal')
                            ->helperText('Si s\'activa, aquest usuari serà el gestor principal i els altres deixaran de ser-ho.')
                            ->default(false),
                    ])
                    ->action(function (array $data) {
                        $departament = $this->getOwnerRecord();
                        $userId = $data['user_id'];
                        
                        // Si es marca com principal, desprincipalitzar els altres
                        if ($data['gestor_principal'] ?? false) {
                            DB::table('departament_gestors')
                                ->where('departament_id', $departament->id)
                                ->update(['gestor_principal' => false]);
                        }

                        // Afegir el nou gestor
                        DB::table('departament_gestors')->insert([
                            'departament_id' => $departament->id,
                            'user_id' => $userId,
                            'gestor_principal' => $data['gestor_principal'] ?? false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Actualitzar gestor_id per compatibilitat
                        if ($data['gestor_principal'] ?? false) {
                            $departament->update(['gestor_id' => $userId]);
                        }
                        
                        // Mostrar notificación de éxito
                        \Filament\Notifications\Notification::make()
                            ->title('Gestor afegit correctament')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('marcar_principal')
                    ->label('Marcar Principal')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(function ($record): bool {
                        return !DB::table('departament_gestors')
                            ->where('departament_id', $this->getOwnerRecord()->id)
                            ->where('user_id', $record->id)
                            ->where('gestor_principal', true)
                            ->exists();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Marcar com Gestor Principal')
                    ->modalDescription('Aquest usuari serà el gestor principal i els altres deixaran de ser-ho.')
                    ->action(function ($record) {
                        $departament = $this->getOwnerRecord();
                        
                        // Desprincipalitzar tots
                        DB::table('departament_gestors')
                            ->where('departament_id', $departament->id)
                            ->update(['gestor_principal' => false]);

                        // Marcar com principal
                        DB::table('departament_gestors')
                            ->where('departament_id', $departament->id)
                            ->where('user_id', $record->id)
                            ->update(['gestor_principal' => true]);

                        // Actualitzar gestor_id per compatibilitat
                        $departament->update(['gestor_id' => $record->id]);
                    }),
                    
                Action::make('desprincipalitzar')
                    ->label('Desprincipalitzar')
                    ->icon('heroicon-o-minus-circle')
                    ->color('gray')
                    ->visible(function ($record): bool {
                        $esPrincipal = DB::table('departament_gestors')
                            ->where('departament_id', $this->getOwnerRecord()->id)
                            ->where('user_id', $record->id)
                            ->where('gestor_principal', true)
                            ->exists();
                            
                        $totalPrincipals = DB::table('departament_gestors')
                            ->where('departament_id', $this->getOwnerRecord()->id)
                            ->where('gestor_principal', true)
                            ->count();
                            
                        return $esPrincipal && $totalPrincipals > 1;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Desprincipalitzar Gestor')
                    ->modalDescription('Aquest usuari deixarà de ser gestor principal però continuarà sent gestor del departament.')
                    ->action(function ($record) {
                        DB::table('departament_gestors')
                            ->where('departament_id', $this->getOwnerRecord()->id)
                            ->where('user_id', $record->id)
                            ->update(['gestor_principal' => false]);

                        // Si era el gestor_id, actualitzar amb un altre principal
                        if ($this->getOwnerRecord()->gestor_id == $record->id) {
                            $nouPrincipal = DB::table('departament_gestors')
                                ->where('departament_id', $this->getOwnerRecord()->id)
                                ->where('gestor_principal', true)
                                ->first();
                                
                            $this->getOwnerRecord()->update([
                                'gestor_id' => $nouPrincipal?->user_id
                            ]);
                        }
                    }),
                    
                Action::make('eliminar_gestor')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Eliminar Gestor del Departament')
                    ->modalDescription('Això eliminarà l\'usuari com a gestor d\'aquest departament.')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Verificar si és l'últim gestor principal
                        $departament = $this->getOwnerRecord();
                        $esPrincipal = DB::table('departament_gestors')
                            ->where('departament_id', $departament->id)
                            ->where('user_id', $record->id)
                            ->where('gestor_principal', true)
                            ->exists();
                            
                        if ($esPrincipal) {
                            $gestorsPrincipals = DB::table('departament_gestors')
                                ->where('departament_id', $departament->id)
                                ->where('gestor_principal', true)
                                ->count();
                                
                            if ($gestorsPrincipals <= 1) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No es pot eliminar l\'\u00faltim gestor principal')
                                    ->body('Aquest departament necessita almenys un gestor principal.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }
                        
                        // Eliminar la relación
                        DB::table('departament_gestors')
                            ->where('departament_id', $departament->id)
                            ->where('user_id', $record->id)
                            ->delete();

                        // Si era el gestor_id, actualitzar amb un altre principal
                        if ($departament->gestor_id == $record->id) {
                            $nouPrincipal = DB::table('departament_gestors')
                                ->where('departament_id', $departament->id)
                                ->where('gestor_principal', true)
                                ->first();
                                
                            $departament->update([
                                'gestor_id' => $nouPrincipal?->user_id
                            ]);
                        }
                        
                        // Mostrar notificación de éxito
                        \Filament\Notifications\Notification::make()
                            ->title('Gestor eliminat correctament')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('eliminar_seleccionats')
                        ->label('Eliminar Seleccionats')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->modalHeading('Eliminar Gestors del Departament')
                        ->modalDescription('Això eliminarà els usuaris seleccionats com a gestors d\'aquest departament.')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $departament = $this->getOwnerRecord();
                            
                            // Verificar si s'està intentant eliminar tots els gestors principals
                            $principalsSeleccionats = 0;
                            $totalPrincipals = DB::table('departament_gestors')
                                ->where('departament_id', $departament->id)
                                ->where('gestor_principal', true)
                                ->count();
                                
                            foreach ($records as $record) {
                                $esPrincipal = DB::table('departament_gestors')
                                    ->where('departament_id', $departament->id)
                                    ->where('user_id', $record->id)
                                    ->where('gestor_principal', true)
                                    ->exists();
                                    
                                if ($esPrincipal) {
                                    $principalsSeleccionats++;
                                }
                            }
                            
                            if ($principalsSeleccionats >= $totalPrincipals) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No es poden eliminar tots els gestors principals')
                                    ->body('Aquest departament necessita almenys un gestor principal.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            // Eliminar els gestors seleccionats
                            foreach ($records as $record) {
                                DB::table('departament_gestors')
                                    ->where('departament_id', $departament->id)
                                    ->where('user_id', $record->id)
                                    ->delete();
                                    
                                // Si era el gestor_id, actualitzar amb un altre principal
                                if ($departament->gestor_id == $record->id) {
                                    $nouPrincipal = DB::table('departament_gestors')
                                        ->where('departament_id', $departament->id)
                                        ->where('gestor_principal', true)
                                        ->first();
                                        
                                    $departament->update([
                                        'gestor_id' => $nouPrincipal?->user_id
                                    ]);
                                }
                            }
                            
                            // Mostrar notificación de éxito
                            \Filament\Notifications\Notification::make()
                                ->title('Gestors eliminats correctament')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Ordenar per gestor principal primer, després per nom
                return $query->orderByRaw('
                    CASE WHEN EXISTS (
                        SELECT 1 FROM departament_gestors dg 
                        WHERE dg.user_id = users.id 
                        AND dg.departament_id = ' . $this->getOwnerRecord()->id . ' 
                        AND dg.gestor_principal = true
                    ) THEN 0 ELSE 1 END, 
                    users.name ASC
                ');
            });
    }
}