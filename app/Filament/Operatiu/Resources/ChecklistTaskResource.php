<?php

namespace App\Filament\Operatiu\Resources;

use App\Filament\Operatiu\Resources\ChecklistTaskResource\Pages;
use App\Filament\Operatiu\Resources\ChecklistTaskResource\RelationManagers;
use App\Models\ChecklistTask;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ChecklistTaskResource extends Resource
{
    protected static ?string $model = ChecklistTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Tasques IT';
    protected static ?string $modelLabel = 'Tasca';
    protected static ?string $pluralModelLabel = 'Les Meves Tasques';
    protected static ?string $navigationLabel = 'Les Meves Tasques';
    
    // Solo visible para usuarios con rol IT
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('it') || auth()->user()->hasRole('admin');
    }
    
    // Filtrar tareas por rol del usuario o asignadas al usuario
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        
        return parent::getEloquentQuery()
            ->where(function($query) use ($user) {
                // Mostrar tareas donde el usuario está asignado específicamente (compatibilidad con sistema anterior)
                $query->where('usuari_assignat_id', $user->id)
                // O tareas con el rol asignado que coincide con alguno de los roles del usuario
                ->orWhere(function($q) use ($user) {
                    if ($user->hasRole('it')) {
                        $q->where('rol_assignat', 'it');
                    }
                    if ($user->hasRole('rrhh')) {
                        $q->orWhere('rol_assignat', 'rrhh');
                    }
                    if ($user->hasRole('gestor')) {
                        $q->orWhere('rol_assignat', 'gestor');
                    }
                })
                // O tareas relacionadas con el propio empleado
                ->orWhereHas('checklistInstance', function ($q) use ($user) {
                    $q->where('empleat_id', $user->id);
                });
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informació de la Tasca')
                    ->schema([
                        TextInput::make('nom')
                            ->required()
                            ->maxLength(255)
                            ->label('Nom de la tasca'),
                            
                        Textarea::make('descripcio')
                            ->label('Descripció')
                            ->columnSpanFull(),
                            
                        Select::make('usuari_assignat_id')
                            ->relationship('usuariAssignat', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Usuari Assignat')
                            ->default(fn () => Auth::id())
                            ->required(),
                            
                        Select::make('rol_assignat')
                            ->options([
                                'it' => 'Equip IT',
                                'rrhh' => 'Recursos Humans',
                                'gestor' => 'Gestor',
                            ])
                            ->required()
                            ->label('Rol Assignat'),
                            
                        Toggle::make('obligatoria')
                            ->default(true)
                            ->label('Tasca Obligatòria'),
                            
                        DateTimePicker::make('data_limit')
                            ->label('Data Límit')
                            ->time(false)
                            ->minDate(now()),
                            
                        Select::make('checklist_instance_id')
                            ->relationship('checklistInstance', 'id')
                            ->label('Instància del Checklist')
                            ->required(),
                            
                        TextInput::make('ordre')
                            ->numeric()
                            ->default(0)
                            ->label('Ordre'),
                            
                        DateTimePicker::make('data_assignacio')
                            ->label('Data Assignació')
                            ->default(now()),
                    ])->columns(2),
                    
                Section::make('Estat de la Tasca')
                    ->schema([
                        Toggle::make('completada')
                            ->label('Marcar com a completada')
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => 
                                $state ? $set('data_completada', now()) : $set('data_completada', null)
                            ),
                            
                        DateTimePicker::make('data_completada')
                            ->label('Data de Completada')
                            ->disabled(fn ($get) => !$get('completada'))
                            ->hidden(fn ($get) => !$get('completada')),
                            
                        Textarea::make('observacions')
                            ->label('Observacions')
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')
                    ->searchable()
                    ->sortable()
                    ->wrap() // Permitir que el texto se ajuste en varias líneas
                    ->label('Tasca'),
                    
                TextColumn::make('checklistInstance.empleat.nom_complet')
                    ->searchable()
                    ->sortable()
                    ->limit(20) // Truncar el texto a 20 caracteres
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return $state ? $state : null;
                    }) // Mostrar el texto completo en un tooltip
                    ->label('Empleat'),
                    
                TextColumn::make('usuariAssignat.name')
                    ->searchable()
                    ->sortable()
                    ->limit(20) // Truncar el texto a 20 caracteres
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return $state ? $state : null;
                    }) // Mostrar el texto completo en un tooltip
                    ->label('Assignada a'),
                    
                BadgeColumn::make('estat')
                    ->getStateUsing(fn (ChecklistTask $record) => $record->getEstatFormatted())
                    ->colors([
                        'success' => 'Completada',
                        'danger' => 'Vencuda',
                        'warning' => 'Propera a vencer',
                        'gray' => 'Pendent',
                    ])
                    ->label('Estat'),
                    
                TextColumn::make('data_limit')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->label('Data Límit'),
                    
                IconColumn::make('obligatoria')
                    ->boolean()
                    ->label('Obligatòria'),
                    
                IconColumn::make('completada')
                    ->boolean()
                    ->label('Completada'),
            ])
            ->defaultSort('data_limit', 'asc')
            ->filters([
                // Filtro para mostrar solo mis tareas asignadas
                Filter::make('mis_tareas')
                    ->label('Mostrar només les meves tasques')
                    ->query(fn (Builder $query): Builder => $query->where('usuari_assignat_id', auth()->id())),
                    
                SelectFilter::make('completada')
                    ->options([
                        '1' => 'Completades',
                        '0' => 'Pendents',
                    ])
                    ->label('Estat'),
                    
                Filter::make('vencudes')
                    ->label('Mostrar només vencudes')
                    ->query(fn (Builder $query): Builder => $query->vencudes()),
                    
                Filter::make('proximes_venciment')
                    ->label('Pròximes a vencer (3 dies)')
                    ->query(fn (Builder $query): Builder => $query->proximesAVencer()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Veure detalls'),
                    
                // Acción para asignar usuario (reemplaza la acción de editar)
                Tables\Actions\Action::make('assignar_usuari')
                    ->label('')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->visible(fn (ChecklistTask $record): bool => 
                        !$record->usuari_assignat_id && 
                        (auth()->user()->hasRole('admin') || 
                         auth()->user()->hasRole($record->rol_assignat))
                    )
                    ->form([
                        Select::make('usuari_assignat_id')
                            ->label('Assignar a usuari')
                            ->options(function (ChecklistTask $record) {
                                // Obtener usuarios con el rol correspondiente a la tarea
                                return \App\Models\User::query()
                                    ->where('actiu', true)
                                    ->whereHas('roles', function ($query) use ($record) {
                                        $query->where('name', $record->rol_assignat);
                                    })
                                    ->orWhere('rol_principal', $record->rol_assignat) // Compatibilidad con sistema anterior
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (ChecklistTask $record, array $data): void {
                        $record->update([
                            'usuari_assignat_id' => $data['usuari_assignat_id'],
                            'data_assignacio' => now(),
                        ]);
                        
                        // Notificar al usuario asignado
                        \App\Jobs\NotificarTascaAssignada::dispatch($record);
                        
                        Filament\Notifications\Notification::make()
                            ->title('Tasca assignada')
                            ->success()
                            ->send();
                    })
                    ->tooltip('Assignar usuari'),
                    
                // Acción para desasignar usuario
                Tables\Actions\Action::make('desassignar_usuari')
                    ->label('')
                    ->icon('heroicon-o-user-minus')
                    ->color('warning')
                    ->visible(fn (ChecklistTask $record): bool => 
                        $record->usuari_assignat_id && 
                        !$record->completada &&
                        (auth()->user()->hasRole('admin') || 
                         auth()->user()->hasRole($record->rol_assignat))
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Desassignar usuari')
                    ->modalDescription('Estàs segur que vols desassignar l\'usuari d\'aquesta tasca?')
                    ->action(function (ChecklistTask $record): void {
                        $record->update([
                            'usuari_assignat_id' => null,
                            'data_assignacio' => null,
                        ]);
                        
                        Filament\Notifications\Notification::make()
                            ->title('Usuari desassignat')
                            ->success()
                            ->send();
                    })
                    ->tooltip('Desassignar usuari'),
                    
                Tables\Actions\Action::make('completar')
                    ->label('')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Completar tasca')
                    ->modalDescription('Estàs segur que vols marcar aquesta tasca com a completada?')
                    ->form([
                        Textarea::make('observacions')
                            ->label('Observacions (opcional)')
                            ->columnSpanFull(),
                    ])
                    ->action(function (ChecklistTask $record, array $data): void {
                        $record->completar(auth()->user(), $data['observacions'] ?? null);
                    })
                    ->visible(fn (ChecklistTask $record): bool => 
                        !$record->completada && 
                        (auth()->user()->hasRole('admin') || 
                         auth()->user()->hasRole($record->rol_assignat) || 
                         $record->usuari_assignat_id === auth()->id())
                    )
                    ->tooltip('Marcar com a completada'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            // RelationManagers\ChecklistInstanceRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChecklistTasks::route('/'),
            'create' => Pages\CreateChecklistTask::route('/create'),
            'view' => Pages\ViewChecklistTask::route('/{record}'),
            'edit' => Pages\EditChecklistTask::route('/{record}/edit'),
        ];
    }    
}
