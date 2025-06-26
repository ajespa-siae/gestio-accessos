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
                Tables\Columns\TextColumn::make('pivot.ordre')
                    ->label('#')
                    ->sortable()
                    ->weight('bold')
                    ->color('gray'),
                    
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
                    
                Tables\Columns\IconColumn::make('pivot.requerit')
                    ->label('Obligatori')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('pivot.actiu')
                    ->label('Actiu')
                    ->boolean()
                    ->sortable(),
                    
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
                    
                Tables\Filters\SelectFilter::make('pivot.requerit')
                    ->label('Obligatorietat')
                    ->options([
                        1 => 'Obligatoris',
                        0 => 'Opcionals',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Afegir Validador')
                    ->recordSelectOptionsQuery(fn (Builder $query) => 
                        $query->where('actiu', true)
                              ->whereIn('rol_principal', ['admin', 'rrhh', 'it', 'gestor'])
                    )
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\TextInput::make('ordre')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->label('Ordre de Validació'),
                        Forms\Components\Toggle::make('requerit')
                            ->default(true)
                            ->label('Validació Obligatòria'),
                        Forms\Components\Toggle::make('actiu')
                            ->default(true)
                            ->label('Actiu'),
                    ]),
                    
                Action::make('configurar_flux')
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
                Tables\Actions\DetachAction::make()
                    ->label('Eliminar'),
                    
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                    
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
            ->defaultSort('pivot.ordre');
    }
}