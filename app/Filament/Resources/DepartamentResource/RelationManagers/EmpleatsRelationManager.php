<?php

namespace App\Filament\Resources\DepartamentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EmpleatsRelationManager extends RelationManager
{
    protected static string $relationship = 'empleats';
    
    protected static ?string $title = 'Empleats del Departament';
    
    protected static ?string $recordTitleAttribute = 'nom_complet';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nom_complet')
                    ->label('Nom Complet')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('nif')
                    ->label('NIF')
                    ->required()
                    ->maxLength(20),
                
                Forms\Components\TextInput::make('correu_personal')
                    ->label('Correu Personal')
                    ->email()
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('carrec')
                    ->label('Càrrec')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Select::make('estat')
                    ->label('Estat')
                    ->options([
                        'actiu' => 'Actiu',
                        'baixa' => 'Baixa',
                        'suspens' => 'Suspens',
                    ])
                    ->default('actiu')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom_complet')
            ->columns([
                Tables\Columns\TextColumn::make('nom_complet')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('nif')
                    ->label('NIF')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('carrec')
                    ->label('Càrrec')
                    ->searchable(),
                
                Tables\Columns\BadgeColumn::make('estat')
                    ->label('Estat')
                    ->colors([
                        'success' => 'actiu',
                        'danger' => 'baixa',
                        'warning' => 'suspens',
                    ]),
                
                Tables\Columns\TextColumn::make('data_alta')
                    ->label('Data Alta')
                    ->date('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estat')
                    ->options([
                        'actiu' => 'Actiu',
                        'baixa' => 'Baixa',
                        'suspens' => 'Suspens',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}