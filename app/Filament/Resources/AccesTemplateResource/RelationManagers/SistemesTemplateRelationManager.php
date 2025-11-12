<?php

namespace App\Filament\Resources\AccesTemplateResource\RelationManagers;

use App\Models\NivellAccesSistema;
use App\Models\Sistema;
use App\Models\SistemaElementExtra;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Fieldset;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;

class SistemesTemplateRelationManager extends RelationManager
{
    protected static string $relationship = 'sistemesTemplate';

    protected static ?string $title = 'Sistemes de la Plantilla';

    protected static ?string $modelLabel = 'Sistema';

    protected static ?string $pluralModelLabel = 'Sistemes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('sistema_id')
                            ->label('Sistema')
                            ->relationship('sistema', 'nom')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                $set('nivell_acces_id', null);
                                // Reset elements extra quan canvia el sistema
                                $set('elementsExtra', []);
                            }),

                        Select::make('nivell_acces_id')
                            ->label("Nivell d'Accés")
                            ->options(function (callable $get) {
                                $sistemaId = $get('sistema_id');
                                if (!$sistemaId) { return []; }
                                return NivellAccesSistema::where('sistema_id', $sistemaId)
                                    ->where('actiu', true)
                                    ->orderBy('ordre')
                                    ->pluck('nom', 'id');
                            })
                            ->searchable()
                            ->required(),
                    ]),

                Grid::make(3)
                    ->schema([
                        TextInput::make('ordre')->numeric()->minValue(1)->default(1)->required(),
                        Toggle::make('actiu')->default(true),
                    ]),

                Fieldset::make('Elements extra')
                    ->schema([
                        TableRepeater::make('elementsExtra')
                            ->relationship('elementsExtra')
                            ->label('Elements extra configurats')
                            ->headers([
                                Header::make('element_extra_id')->label('Element')->width('40%'),
                                Header::make('opcio_seleccionada')->label('Opció')->width('30%'),
                                Header::make('valor_text_lliure')->label('Text personalitzat')->width('30%'),
                            ])
                            ->schema([
                                Select::make('element_extra_id')
                                    ->label('Element')
                                    ->options(function (callable $get) {
                                        $sistemaId = $get('../../sistema_id');
                                        if (!$sistemaId) { return []; }
                                        return SistemaElementExtra::where('sistema_id', $sistemaId)
                                            ->where('actiu', true)
                                            ->orderBy('ordre')
                                            ->pluck('nom', 'id');
                                    })
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('opcio_seleccionada', null);
                                        $set('valor_text_lliure', null);
                                    }),

                                Select::make('opcio_seleccionada')
                                    ->label('Opció')
                                    ->options(function (callable $get) {
                                        $elementId = $get('element_extra_id');
                                        if (!$elementId) { return []; }
                                        $element = SistemaElementExtra::find($elementId);
                                        if (!$element || !$element->teOpcions()) { return []; }
                                        return array_combine($element->opcions_disponibles, $element->opcions_disponibles);
                                    })
                                    ->visible(function (callable $get) {
                                        $elementId = $get('element_extra_id');
                                        if (!$elementId) { return false; }
                                        $element = SistemaElementExtra::find($elementId);
                                        return $element && $element->teOpcions();
                                    }),

                                TextInput::make('valor_text_lliure')
                                    ->label('Text personalitzat')
                                    ->placeholder('Text...')
                                    ->visible(function (callable $get) {
                                        $elementId = $get('element_extra_id');
                                        if (!$elementId) { return false; }
                                        $element = SistemaElementExtra::find($elementId);
                                        return $element && $element->acceptaTextLliure();
                                    }),

                                TextInput::make('ordre')->numeric()->minValue(1)->default(1),
                                Toggle::make('actiu')->default(true),
                            ])
                            ->addActionLabel('Afegir element')
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->emptyLabel('Sense elements extra')
                            ->columnSpan('full'),
                    ])
                    ->columns(1)
                    ->visible(function (callable $get) {
                        $sistemaId = $get('sistema_id');
                        if (!$sistemaId) {
                            return false;
                        }
                        $sistema = \App\Models\Sistema::find($sistemaId);
                        return $sistema && method_exists($sistema, 'teElementsComplexos') && $sistema->teElementsComplexos();
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sistema.nom')->label('Sistema')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nivellAcces.nom')->label("Nivell d'Accés"),
                Tables\Columns\TextColumn::make('elementsExtra_count')->label('Elements extra')->counts('elementsExtra')->badge()->color('info'),
                Tables\Columns\IconColumn::make('actiu')->boolean(),
                Tables\Columns\TextColumn::make('ordre')->sortable(),
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
