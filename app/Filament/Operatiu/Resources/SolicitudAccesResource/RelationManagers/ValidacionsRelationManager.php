<?php

namespace App\Filament\Operatiu\Resources\SolicitudAccesResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\DateTimePicker;

class ValidacionsRelationManager extends RelationManager
{
    protected static string $relationship = 'validacions';

    protected static ?string $title = 'Validacions';

    protected static ?string $modelLabel = 'Validació';

    protected static ?string $pluralModelLabel = 'Validacions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('usuari_validador_id')
                    ->label('Validador')
                    ->options(User::role(['gestor', 'admin'])->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->disabled(fn ($record) => $record && $record->exists),
                    
                Select::make('estat')
                    ->label('Estat')
                    ->options([
                        'pendent' => '⏳ Pendent',
                        'aprovada' => '✅ Aprovada',
                        'rebutjada' => '❌ Rebutjada',
                    ])
                    ->default('pendent')
                    ->required(),
                    
                Textarea::make('comentaris')
                    ->label('Comentaris')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                    
                DateTimePicker::make('data_validacio')
                    ->label('Data de validació')
                    ->default(now())
                    ->displayFormat('d/m/Y H:i'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('validador.name')
                    ->label('Validador')
                    ->searchable()
                    ->sortable(),
                    
                BadgeColumn::make('estat')
                    ->label('Estat')
                    ->colors([
                        'warning' => 'pendent',
                        'success' => 'aprovada',
                        'danger' => 'rebutjada',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pendent' => '⏳ Pendent',
                        'aprovada' => '✅ Aprovada',
                        'rebutjada' => '❌ Rebutjada',
                        default => $state,
                    })
                    ->sortable(),
                    
                TextColumn::make('comentaris')
                    ->label('Comentaris')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->comentaris),
                    
                TextColumn::make('data_validacio')
                    ->label('Data de validació')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                IconColumn::make('es_obligatoria')
                    ->label('Obligatòria')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estat')
                    ->label('Filtrar per estat')
                    ->options([
                        'pendent' => '⏳ Pendent',
                        'aprovada' => '✅ Aprovada',
                        'rebutjada' => '❌ Rebutjada',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Afegir Validació')
                    ->visible(fn () => 
                        $this->getOwnerRecord()->estat === 'pendent' && 
                        auth()->user()->hasRole('admin')
                    )
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['es_obligatoria'] = true;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('aprovar')
                    ->label('')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprovar validació')
                    ->modalDescription('Estàs segur que vols aprovar aquesta validació?')
                    ->form([
                        Textarea::make('comentaris')
                            ->label('Comentaris (opcionals)')
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data, $record): void {
                        $record->update([
                            'estat' => 'aprovada',
                            'comentaris' => $data['comentaris'] ?? null,
                            'data_validacio' => now(),
                            'usuari_validador_id' => auth()->id(),
                        ]);
                        
                        // Actualizar el estado de la solicitud principal
                        $record->solicitudAcces->comprovarEstatValidacions();
                    })
                    ->visible(fn ($record): bool => 
                        $record->estat === 'pendent' && 
                        ($record->usuari_validador_id === auth()->id() || auth()->user()->hasRole('admin'))
                    ),
                    
                Tables\Actions\Action::make('rebutjar')
                    ->label('')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rebutjar validació')
                    ->modalDescription('Estàs segur que vols rebutjar aquesta validació?')
                    ->form([
                        Textarea::make('comentaris')
                            ->label('Raó del rebuig')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data, $record): void {
                        $record->update([
                            'estat' => 'rebutjada',
                            'comentaris' => $data['comentaris'],
                            'data_validacio' => now(),
                            'usuari_validador_id' => auth()->id(),
                        ]);
                        
                        // Actualizar el estado de la solicitud principal
                        $record->solicitudAcces->comprovarEstatValidacions();
                    })
                    ->visible(fn ($record): bool => 
                        $record->estat === 'pendent' && 
                        ($record->usuari_validador_id === auth()->id() || auth()->user()->hasRole('admin'))
                    ),
                    
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => 
                        $record->estat === 'pendent' && 
                        auth()->user()->hasRole('admin')
                    ),
                    
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => 
                        $record->estat === 'pendent' && 
                        auth()->user()->hasRole('admin')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => 
                            $this->getOwnerRecord()->estat === 'pendent' && 
                            auth()->user()->hasRole('admin')
                        ),
                ]),
            ]);
    }
}
