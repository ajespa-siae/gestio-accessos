<?php

namespace App\Filament\Resources\SistemaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class ElementsExtraRelationManager extends RelationManager
{
    protected static string $relationship = 'elementsExtra';

    protected static ?string $title = 'Opcions Sistema';

    protected static ?string $modelLabel = 'Element Extra';

    protected static ?string $pluralModelLabel = 'Elements Extra';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informació de l\'Element')
                    ->schema([
                        TextInput::make('nom')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(2),
                            
                        Textarea::make('descripcio')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                            
                        Select::make('tipus')
                            ->required()
                            ->options([
                                'modul' => 'Mòdul',
                                'funcionalitat' => 'Funcionalitat',
                                'recurs' => 'Recurs',
                                'nivell' => 'Nivell',
                                'configuracio' => 'Configuració',
                            ])
                            ->helperText('Tipus d\'element que representa'),
                            
                        TextInput::make('ordre')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->helperText('Ordre d\'aparició al formulari'),
                            
                        Toggle::make('actiu')
                            ->default(true)
                            ->helperText('Element disponible per sol·licituds'),
                    ])
                    ->columns(3),
                    
                Section::make('Configuració d\'Opcions')
                    ->schema([
                        TagsInput::make('opcions_disponibles')
                            ->label('Opcions Disponibles')
                            ->placeholder('Escriu una opció i prem Enter')
                            ->helperText('Opcions predefinides que l\'usuari pot seleccionar. Deixa buit si només vols text lliure.')
                            ->columnSpanFull(),
                            
                        Toggle::make('permet_text_lliure')
                            ->label('Permet Text Lliure')
                            ->default(false)
                            ->helperText('Permet a l\'usuari introduir text personalitzat a més de les opcions predefinides'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            ->columns([
                TextColumn::make('nom')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                BadgeColumn::make('tipus')
                    ->color(fn (string $state): string => match ($state) {
                        'modul' => 'info',
                        'funcionalitat' => 'success',
                        'recurs' => 'warning',
                        'nivell' => 'primary',
                        'configuracio' => 'secondary',
                        default => 'gray',
                    }),
                    
                TextColumn::make('opcions_disponibles')
                    ->label('Opcions')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'Sense opcions';
                        }
                        return is_array($state) ? implode(', ', $state) : $state;
                    })
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (empty($state)) {
                            return null;
                        }
                        return is_array($state) ? implode(', ', $state) : $state;
                    }),
                    
                IconColumn::make('permet_text_lliure')
                    ->label('Text Lliure')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),
                    
                TextColumn::make('ordre')
                    ->sortable()
                    ->alignCenter(),
                    
                IconColumn::make('actiu')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipus')
                    ->options([
                        'modul' => 'Mòdul',
                        'funcionalitat' => 'Funcionalitat',
                        'recurs' => 'Recurs',
                        'nivell' => 'Nivell',
                        'configuracio' => 'Configuració',
                    ]),
                    
                Tables\Filters\Filter::make('actius')
                    ->label('Només Actius')
                    ->query(fn (Builder $query): Builder => $query->where('actiu', true))
                    ->default(),
                    
                Tables\Filters\Filter::make('amb_opcions')
                    ->label('Amb Opcions Predefinides')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('opcions_disponibles')),
                    
                Tables\Filters\Filter::make('text_lliure')
                    ->label('Permet Text Lliure')
                    ->query(fn (Builder $query): Builder => $query->where('permet_text_lliure', true)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Afegir Element Extra')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Crear Element Extra')
                    ->modalWidth('2xl'),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Editar Element Extra')
                    ->modalWidth('2xl'),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Element Extra')
                    ->modalDescription('Aquesta acció eliminarà l\'element extra i totes les seves configuracions. Les sol·licituds existents que utilitzin aquest element no es veuran afectades.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('ordre')
            ->reorderable('ordre')
            ->emptyStateHeading('Cap element extra configurat')
            ->emptyStateDescription('Aquest sistema utilitza només nivells d\'accés simples. Afegeix elements extra per crear un formulari híbrid.')
            ->emptyStateIcon('heroicon-o-puzzle-piece');
    }
}
