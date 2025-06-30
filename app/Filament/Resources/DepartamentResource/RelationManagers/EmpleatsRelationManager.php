<?php

namespace App\Filament\Resources\DepartamentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Empleat;
use App\Models\User;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class EmpleatsRelationManager extends RelationManager
{
    protected static string $relationship = 'empleats';

    protected static ?string $title = 'Empleats';

    protected static ?string $modelLabel = 'Empleat';

    protected static ?string $pluralModelLabel = 'Empleats';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dades Personals')
                    ->schema([
                        TextInput::make('nom_complet')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        
                        TextInput::make('nif')
                            ->label('NIF')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),
                            
                        TextInput::make('correu_personal')
                            ->label('Correu Personal')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Dades Laborals')
                    ->schema([
                        TextInput::make('carrec')
                            ->required()
                            ->maxLength(255),
                            
                        Select::make('estat')
                            ->options([
                                'actiu' => 'Actiu',
                                'baixa' => 'Baixa',
                                'suspens' => 'Suspens',
                            ])
                            ->default('actiu')
                            ->required(),
                            
                        DateTimePicker::make('data_alta')
                            ->label('Data Alta')
                            ->default(now())
                            ->required(),
                            
                        DateTimePicker::make('data_baixa')
                            ->label('Data Baixa')
                            ->visible(fn ($get) => $get('estat') === 'baixa'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Observacions')
                    ->schema([
                        Textarea::make('observacions')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom_complet')
            ->columns([
                TextColumn::make('nom_complet')
                    ->label('Nom Complet')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('nif')
                    ->label('NIF')
                    ->searchable(),
                    
                TextColumn::make('carrec')
                    ->searchable()
                    ->sortable(),
                    
                BadgeColumn::make('estat')
                    ->colors([
                        'success' => 'actiu',
                        'danger' => 'baixa',
                        'warning' => 'suspens',
                    ]),
                    
                TextColumn::make('data_alta')
                    ->label('Data Alta')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                TextColumn::make('data_baixa')
                    ->label('Data Baixa')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                    
                TextColumn::make('identificador_unic')
                    ->label('ID Únic')
                    ->copyable()
                    ->copyMessage('ID copiat!')
                    ->copyMessageDuration(1500),
                    
                TextColumn::make('usuariCreador.name')
                    ->label('Creat per')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('estat')
                    ->options([
                        'actiu' => 'Actiu',
                        'baixa' => 'Baixa',
                        'suspens' => 'Suspens',
                    ]),
                    
                Filter::make('data_alta')
                    ->form([
                        DateTimePicker::make('des_de')
                            ->label('Desde'),
                        DateTimePicker::make('fins_a')
                            ->label('Fins a'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['des_de'],
                                fn (Builder $query, $date): Builder => $query->whereDate('data_alta', '>=', $date),
                            )
                            ->when(
                                $data['fins_a'],
                                fn (Builder $query, $date): Builder => $query->whereDate('data_alta', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalHeading('Crear Nou Empleat')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['usuari_creador_id'] = auth()->id();
                        $data['identificador_unic'] = $this->generarIdentificadorUnic();
                        return $data;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                
                EditAction::make(),
                
                Action::make('baixa')
                    ->label('Donar de Baixa')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->visible(fn (Empleat $record): bool => $record->estat === 'actiu')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Baixa d\'Empleat')
                    ->modalDescription('Aquesta acció crearà automàticament les tasques d\'offboarding per IT.')
                    ->form([
                        Textarea::make('observacions')
                            ->label('Motiu de la baixa')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Empleat $record, array $data): void {
                        $record->update([
                            'estat' => 'baixa',
                            'data_baixa' => now(),
                            'observacions' => $data['observacions'],
                        ]);
                        
                        // Aquí s'hauria de disparar el job d'offboarding
                        // dispatch(new CrearChecklistOffboarding($record));
                    }),
                    
                Action::make('reactivar')
                    ->label('Reactivar')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (Empleat $record): bool => $record->estat === 'baixa')
                    ->requiresConfirmation()
                    ->action(function (Empleat $record): void {
                        $record->update([
                            'estat' => 'actiu',
                            'data_baixa' => null,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('data_alta', 'desc');
    }
    
    /**
     * Genera un identificador únic per l'empleat
     */
    private function generarIdentificadorUnic(): string
    {
        do {
            $identificador = 'EMP-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (Empleat::where('identificador_unic', $identificador)->exists());
        
        return $identificador;
    }
}