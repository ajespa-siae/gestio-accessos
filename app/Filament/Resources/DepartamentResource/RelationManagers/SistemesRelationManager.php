<?php

namespace App\Filament\Resources\DepartamentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Sistema;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Actions\DetachBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\Filter;

class SistemesRelationManager extends RelationManager
{
    protected static string $relationship = 'sistemes';

    protected static ?string $title = 'Sistemes Disponibles';

    protected static ?string $modelLabel = 'Sistema';

    protected static ?string $pluralModelLabel = 'Sistemes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('sistema_id')
                    ->label('Sistema')
                    ->relationship('sistemes', 'nom')
                    ->searchable()
                    ->required(),
                    
                Toggle::make('acces_per_defecte')
                    ->label('Accés per Defecte')
                    ->helperText('Si està activat, els empleats d\'aquest departament tindran accés automàtic a aquest sistema.')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            ->columns([
                TextColumn::make('nom')
                    ->label('Nom del Sistema')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Sistema $record): string => $record->descripcio ?? ''),
                    
                TextColumn::make('nivellsAcces_count')
                    ->label('Nivells d\'Accés')
                    ->counts('nivellsAcces')
                    ->badge()
                    ->color('info'),
                    
                TextColumn::make('validadors_count')
                    ->label('Validadors')
                    ->counts('validadors')
                    ->badge()
                    ->color('warning'),
                    
                ToggleColumn::make('pivot.acces_per_defecte')
                    ->label('Accés per Defecte')
                    ->beforeStateUpdated(function ($record, $state) {
                        // Aquí pots afegir lògica abans d'actualitzar
                    }),
                    
                IconColumn::make('actiu')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                TextColumn::make('created_at')
                    ->label('Afegit al Departament')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('actius')
                    ->label('Només Sistemes Actius')
                    ->query(fn (Builder $query): Builder => $query->where('sistemes.actiu', true))
                    ->default(),
                    
                Filter::make('acces_defecte')
                    ->label('Amb Accés per Defecte')
                    ->query(fn (Builder $query): Builder => $query->wherePivot('acces_per_defecte', true)),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Afegir Sistema')
                    ->modalHeading('Afegir Sistema al Departament')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->where('actiu', true))
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Toggle::make('acces_per_defecte')
                            ->label('Accés per Defecte')
                            ->helperText('Els empleats d\'aquest departament tindran accés automàtic a aquest sistema.')
                            ->default(false),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Configurar')
                    ->modalHeading('Configurar Sistema per al Departament')
                    ->form([
                        Toggle::make('acces_per_defecte')
                            ->label('Accés per Defecte')
                            ->helperText('Els empleats d\'aquest departament tindran accés automàtic a aquest sistema.')
                    ]),
                    
                DetachAction::make()
                    ->label('Eliminar')
                    ->modalHeading('Eliminar Sistema del Departament')
                    ->modalDescription('Això eliminarà l\'associació entre el sistema i el departament, però no el sistema en si.')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->label('Eliminar Seleccionats')
                        ->modalHeading('Eliminar Sistemes del Departament')
                        ->modalDescription('Això eliminarà les associacions seleccionades entre els sistemes i el departament.')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('nom');
    }
}