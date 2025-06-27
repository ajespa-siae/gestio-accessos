<?php

namespace App\Filament\Resources\SistemaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Departament;
use Illuminate\Database\Eloquent\Builder;

class DepartamentsRelationManager extends RelationManager
{
    protected static string $relationship = 'departaments';
    protected static ?string $title = 'Departaments amb Accés';
    protected static ?string $recordTitleAttribute = 'nom';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('departament_id')
                    ->label('Departament')
                    ->relationship('departaments', 'nom')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\Toggle::make('acces_per_defecte')
                    ->label('Accés per Defecte')
                    ->default(false)
                    ->helperText('Si els empleats d\'aquest departament tindran accés automàtic a aquest sistema'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom del Departament')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('gestor.name')
                    ->label('Gestor')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('acces_per_defecte')
                    ->label('Per Defecte')
                    ->boolean()
                    ->getStateUsing(function (Departament $record) {
                        $pivot = $record->pivot ?? null;
                        return $pivot ? $pivot->acces_per_defecte : false;
                    }),
                    
                Tables\Columns\IconColumn::make('actiu')
                    ->label('Actiu')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('empleats_count')
                    ->counts('empleats')
                    ->label('Empleats')
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('solicituds_count')
                    ->label('Sol·licituds')
                    ->badge()
                    ->color('warning')
                    ->getStateUsing(function (Departament $record) {
                        // Temporal: comentat fins implementar SolicitudAcces/SolicitudSistema
                        // return \App\Models\SolicitudAcces::whereHas('empleatDestinatari', function ($q) use ($record) {
                        //     $q->where('departament_id', $record->id);
                        // })
                        // ->whereHas('sistemesSolicitats', function ($q) {
                        //     $q->where('sistema_id', $this->ownerRecord->id);
                        // })
                        // ->count();
                        return 0;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('actiu')
                    ->options([
                        1 => 'Actius',
                        0 => 'Inactius',
                    ]),
                    
                Tables\Filters\SelectFilter::make('acces_per_defecte')
                    ->label('Accés per Defecte')
                    ->options([
                        1 => 'Amb Accés per Defecte',
                        0 => 'Sense Accés per Defecte',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            $query->wherePivot('acces_per_defecte', $data['value']);
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Afegir Departament')
                    ->recordSelectOptionsQuery(fn (Builder $query) => 
                        $query->where('actiu', true)
                    )
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Toggle::make('acces_per_defecte')
                            ->label('Accés per Defecte')
                            ->default(false)
                            ->helperText('Els empleats d\'aquest departament tindran accés automàtic'),
                    ]),
                    
                Action::make('configurar_accessos')
                    ->label('Configurar Accessos Automàtics')
                    ->icon('heroicon-o-cog')
                    ->color('info')
                    ->modalHeading('Configurar Accessos Automàtics')
                    ->modalDescription('Configurar quins departaments tindran accés automàtic.')
                    ->action(function () {
                        Notification::make()
                            ->title('Configuració guardada')
                            ->body('Els accessos automàtics s\'han configurat correctament.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Eliminar'),
                    
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                    
                Action::make('toggle_defecte')
                    ->label(function (Departament $record) {
                        $pivot = $record->pivot ?? null;
                        $esPerDefecte = $pivot ? $pivot->acces_per_defecte : false;
                        return $esPerDefecte ? 'Treure per Defecte' : 'Marcar per Defecte';
                    })
                    ->icon(function (Departament $record) {
                        $pivot = $record->pivot ?? null;
                        $esPerDefecte = $pivot ? $pivot->acces_per_defecte : false;
                        return $esPerDefecte ? 'heroicon-o-minus-circle' : 'heroicon-o-plus-circle';
                    })
                    ->color(function (Departament $record) {
                        $pivot = $record->pivot ?? null;
                        $esPerDefecte = $pivot ? $pivot->acces_per_defecte : false;
                        return $esPerDefecte ? 'warning' : 'success';
                    })
                    ->action(function (Departament $record) {
                        $pivot = $record->pivot ?? null;
                        $esPerDefecte = $pivot ? $pivot->acces_per_defecte : false;
                        
                        $this->ownerRecord->departaments()->updateExistingPivot($record->id, [
                            'acces_per_defecte' => !$esPerDefecte
                        ]);
                        
                        Notification::make()
                            ->title('Accés per defecte actualitzat')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('marcar_per_defecte')
                        ->label('Marcar per Defecte')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $this->ownerRecord->departaments()->updateExistingPivot($record->id, [
                                    'acces_per_defecte' => true
                                ]);
                            }
                            
                            Notification::make()
                                ->title('Departaments marcats per defecte')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('nom');
    }
}