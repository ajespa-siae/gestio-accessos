<?php

namespace App\Filament\Operatiu\Resources\SolicitudAccesResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Sistema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class SolicitudSistemesRelationManager extends RelationManager
{
    protected static string $relationship = 'sistemesSolicitats';

    protected static ?string $title = 'Sistemes Sol·licitats';

    protected static ?string $modelLabel = 'Sistema Sol·licitat';

    protected static ?string $pluralModelLabel = 'Sistemes Sol·licitats';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('sistema_id')
                    ->label('Sistema')
                    ->options(Sistema::pluck('nom', 'id'))
                    ->searchable()
                    ->required()
                    ->disabled(fn ($record) => $record && $record->exists),
                    
                Textarea::make('descripcio')
                    ->label('Descripció de l\'accés')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
                    
                Select::make('nivell_acces')
                    ->label('Nivell d\'Accés')
                    ->options([
                        'lectura' => 'Lectura',
                        'escriptura' => 'Escriptura',
                        'administracio' => 'Administració',
                    ])
                    ->required()
                    ->default('lectura'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sistema.nom')
            ->columns([
                TextColumn::make('sistema.nom')
                    ->label('Sistema')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('descripcio')
                    ->label('Descripció')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->descripcio),
                    
                BadgeColumn::make('nivell_acces')
                    ->label('Nivell d\'Accés')
                    ->colors([
                        'gray' => 'lectura',
                        'info' => 'escriptura',
                        'warning' => 'administracio',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'lectura' => 'Lectura',
                        'escriptura' => 'Escriptura',
                        'administracio' => 'Administració',
                        default => $state,
                    })
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('Data de sol·licitud')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Afegir Sistema')
                    ->visible(fn () => $this->getOwnerRecord()->estat === 'pendent')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['usuari_creador_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->solicitudAcces->estat === 'pendent'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->solicitudAcces->estat === 'pendent'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => $this->getOwnerRecord()->estat === 'pendent'),
                ]),
            ]);
    }
}
