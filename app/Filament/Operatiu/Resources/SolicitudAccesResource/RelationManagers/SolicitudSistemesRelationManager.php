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
                    ->disabled(fn ($record) => $record && $record->exists)
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('nivell_acces_id', null)),
                    
                Select::make('nivell_acces_id')
                    ->label('Nivell d\'Accés')
                    ->options(function (callable $get) {
                        $sistemaId = $get('sistema_id');
                        if (!$sistemaId) {
                            return [];
                        }
                        
                        return \App\Models\NivellAccesSistema::where('sistema_id', $sistemaId)
                            ->where('actiu', true)
                            ->orderBy('ordre')
                            ->pluck('nom', 'id');
                    })
                    ->searchable()
                    ->required(),
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
                    
                TextColumn::make('nivellAcces.nom')
                    ->label('Nivell d\'Accés')
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
                    ->visible(function ($record) {
                        // Verificar que solicitudAcces no sea nulo antes de acceder a estat
                        return $record->solicitud && $record->solicitud->estat === 'pendent';
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) {
                        // Verificar que solicitudAcces no sea nulo antes de acceder a estat
                        return $record->solicitud && $record->solicitud->estat === 'pendent';
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => $this->getOwnerRecord()->estat === 'pendent'),
                ]),
            ]);
    }
}
