<?php

namespace App\Filament\Resources\SistemaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\NivellAccesSistema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class NivellsAccesRelationManager extends RelationManager
{
    protected static string $relationship = 'nivellsAcces';
    protected static ?string $title = 'Nivells d\'Accés';
    protected static ?string $recordTitleAttribute = 'nom';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nom')
                    ->required()
                    ->maxLength(255)
                    ->label('Nom del Nivell')
                    ->placeholder('Consulta, Gestió, Supervisor...'),
                    
                Forms\Components\Textarea::make('descripcio')
                    ->maxLength(500)
                    ->label('Descripció')
                    ->rows(3),
                    
                Forms\Components\TextInput::make('ordre')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->label('Ordre')
                    ->minValue(1)
                    ->maxValue(100),
                    
                Forms\Components\Toggle::make('actiu')
                    ->default(true)
                    ->label('Nivell Actiu'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            ->columns([
                Tables\Columns\TextColumn::make('ordre')
                    ->label('#')
                    ->sortable()
                    ->weight('bold')
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom del Nivell')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('descripcio')
                    ->label('Descripció')
                    ->limit(40)
                    ->tooltip(fn (NivellAccesSistema $record) => $record->descripcio),
                    
                Tables\Columns\IconColumn::make('actiu')
                    ->label('Actiu')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('solicituds_count')
                    ->label('Sol·licituds')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(function (NivellAccesSistema $record) {
                        // Temporal: comentat fins implementar SolicitudSistema
                        // return \App\Models\SolicitudSistema::where('nivell_acces_id', $record->id)->count();
                        return 0;
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creat')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('actiu')
                    ->options([
                        1 => 'Actius',
                        0 => 'Inactius',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nou Nivell')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['sistema_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
                    
                Action::make('toggle_actiu')
                    ->label(fn (NivellAccesSistema $record) => $record->actiu ? 'Desactivar' : 'Activar')
                    ->icon(fn (NivellAccesSistema $record) => $record->actiu ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (NivellAccesSistema $record) => $record->actiu ? 'warning' : 'success')
                    ->action(function (NivellAccesSistema $record) {
                        $record->update(['actiu' => !$record->actiu]);
                        
                        Notification::make()
                            ->title('Nivell ' . ($record->actiu ? 'activat' : 'desactivat'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['actiu' => true]);
                            }
                        }),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['actiu' => false]);
                            }
                        }),
                ]),
            ])
            ->defaultSort('ordre')
            ->reorderable('ordre')
            ->paginated(false);
    }
}