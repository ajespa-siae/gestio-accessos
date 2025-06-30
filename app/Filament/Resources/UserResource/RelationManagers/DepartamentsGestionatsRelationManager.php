<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Departament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DepartamentsGestionatsRelationManager extends RelationManager
{
    protected static string $relationship = 'departamentsGestionats';

    protected static ?string $recordTitleAttribute = 'nom';

    protected static ?string $title = 'Departaments Gestionats';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('departament_id')
                ->label('Departament')
                ->options(Departament::where('actiu', true)->pluck('nom', 'id'))
                ->required()
                ->searchable(),

            Forms\Components\Toggle::make('gestor_principal')
                ->label('Gestor Principal')
                ->helperText('Només un gestor principal per departament')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Departament'),

                Tables\Columns\IconColumn::make('pivot.gestor_principal')
                    ->label('Principal')
                    ->boolean(),

                Tables\Columns\IconColumn::make('actiu')
                    ->label('Actiu')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Toggle::make('gestor_principal')
                            ->label('Gestor Principal')
                            ->default(false),
                    ])
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->rol_principal !== 'gestor';
    }
}
