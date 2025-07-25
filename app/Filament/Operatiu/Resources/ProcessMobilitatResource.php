<?php

namespace App\Filament\Operatiu\Resources;

use App\Filament\Operatiu\Resources\ProcessMobilitatResource\Pages;
use App\Filament\Operatiu\Resources\ProcessMobilitatResource\RelationManagers;
use App\Models\ProcessMobilitat;
use App\Models\Empleat;
use App\Models\Departament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProcessMobilitatResource extends Resource
{
    protected static ?string $model = ProcessMobilitat::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Gestió RRHH';
    protected static ?string $modelLabel = 'Procés de Mobilitat';
    protected static ?string $pluralModelLabel = 'Processos de Mobilitat';
    protected static ?string $navigationLabel = 'Mobilitat';
    
    // Utilitza la policy per controlar l'accés
    // La policy ja controla que només RRHH i admin puguin accedir

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informació del Procés')
                    ->schema([
                        TextInput::make('identificador_unic')
                            ->label('Identificador')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn () => ProcessMobilitat::generarIdentificador())
                            ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
                            
                        Select::make('empleat_id')
                            ->label('Empleat/da')
                            ->relationship('empleat', 'nom_complet')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $empleat = Empleat::find($state);
                                    if ($empleat && $empleat->departament_id) {
                                        $set('departament_actual_id', $empleat->departament_id);
                                    }
                                }
                            }),
                            
                        Select::make('departament_actual_id')
                            ->label('Departament Actual')
                            ->relationship('departamentActual', 'nom')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                            
                        Select::make('departament_nou_id')
                            ->label('Nou Departament')
                            ->relationship('departamentNou', 'nom')
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Textarea::make('justificacio')
                            ->label('Justificació')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Section::make('Estat del Procés')
                    ->schema([
                        Select::make('estat')
                            ->label('Estat')
                            ->options([
                                'pendent_dept_actual' => 'Pendent Dept. Actual',
                                'pendent_dept_nou' => 'Pendent Dept. Nou',
                                'validant' => 'Validant',
                                'aprovada' => 'Aprovada',
                                'finalitzada' => 'Finalitzada'
                            ])
                            ->default('pendent_dept_actual')
                            ->disabled(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                            ->required(),
                            
                        DateTimePicker::make('data_finalitzacio')
                            ->label('Data Finalització')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('identificador_unic')
                    ->label('Identificador')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('empleat.nom_complet')
                    ->label('Empleat/da')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('departamentActual.nom')
                    ->label('Dept. Actual')
                    ->sortable(),
                    
                TextColumn::make('departamentNou.nom')
                    ->label('Dept. Nou')
                    ->sortable(),
                    
                BadgeColumn::make('estat')
                    ->label('Estat')
                    ->colors([
                        'warning' => 'pendent_dept_actual',
                        'info' => 'pendent_dept_nou',
                        'primary' => 'validant',
                        'success' => 'aprovada',
                        'success' => 'finalitzada',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendent_dept_actual' => 'Pendent Dept. Actual',
                        'pendent_dept_nou' => 'Pendent Dept. Nou',
                        'validant' => 'Validant',
                        'aprovada' => 'Aprovada',
                        'finalitzada' => 'Finalitzada',
                        default => $state
                    }),
                    
                TextColumn::make('usuariSolicitant.name')
                    ->label('Sol·licitat per')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('created_at')
                    ->label('Data Creació')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('data_finalitzacio')
                    ->label('Data Finalització')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Pendent'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estat')
                    ->label('Estat')
                    ->options([
                        'pendent_dept_actual' => 'Pendent Dept. Actual',
                        'pendent_dept_nou' => 'Pendent Dept. Nou',
                        'validant' => 'Validant',
                        'aprovada' => 'Aprovada',
                        'finalitzada' => 'Finalitzada'
                    ]),
                    
                SelectFilter::make('departament_actual_id')
                    ->label('Departament Actual')
                    ->relationship('departamentActual', 'nom'),
                    
                SelectFilter::make('departament_nou_id')
                    ->label('Departament Nou')
                    ->relationship('departamentNou', 'nom'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Veure detalls'),
                    
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('admin')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcessMobilitats::route('/'),
            'create' => Pages\CreateProcessMobilitat::route('/create'),
            'view' => Pages\ViewProcessMobilitat::route('/{record}'),
            'edit' => Pages\EditProcessMobilitat::route('/{record}/edit'),
        ];
    }
}
