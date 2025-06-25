<?php

namespace App\Filament\Resources\DepartamentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SistemesRelationManager extends RelationManager
{
    protected static string $relationship = 'sistemes';
    
    protected static ?string $title = 'Sistemes Associats';
    
    protected static ?string $recordTitleAttribute = 'nom';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nom')
                    ->label('Nom del Sistema')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Textarea::make('descripcio')
                    ->label('Descripció')
                    ->maxLength(65535),
                
                Forms\Components\Toggle::make('actiu')
                    ->label('Sistema Actiu')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Sistema')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('descripcio')
                    ->label('Descripció')
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('nivellsAcces_count')
                    ->label('Nivells d\'Accés')
                    ->counts('nivellsAcces')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\IconColumn::make('actiu')
                    ->label('Actiu')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('actiu')
                    ->label('Actiu'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['nom', 'descripcio']),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}