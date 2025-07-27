<?php

namespace App\Filament\Operatiu\Resources;

use App\Filament\Operatiu\Resources\SolicitudAccesResource\Pages;
use App\Filament\Operatiu\Resources\SolicitudAccesResource\RelationManagers;
use App\Models\SolicitudAcces;
use App\Models\Empleat;
use App\Models\User;
use App\Models\Sistema;
use App\Models\SistemaElementExtra;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SolicitudAccesResource extends Resource
{
    protected static ?string $model = SolicitudAcces::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Gestió de Sol·licituds';
    protected static ?string $modelLabel = 'Sol·licitud';
    protected static ?string $pluralModelLabel = 'Les Meves Sol·licituds';
    protected static ?string $navigationLabel = 'Les Meves Sol·licituds';
    
    // Solo visible para usuarios con rol gestor o admin
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('gestor') || auth()->user()->hasRole('admin');
    }
    
    // Filtrar solo las solicitudes del usuario actual
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['empleatDestinatari', 'usuariSolicitant', 'sistemesSolicitats.sistema']);
            
        if (!auth()->user()->hasRole('admin')) {
            // Solo mostrar las solicitudes donde el usuario es el solicitante
            $query->where('usuari_solicitant_id', auth()->id());
            
            // Si en el futuro se necesita filtrar por empleado destinatario asociado al usuario,
            // habría que implementar la relación adecuada en el modelo Empleat
        }
        
        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informació de la Sol·licitud')
                    ->schema([
                        Select::make('empleat_destinatari_id')
                            ->label('Empleat Destinatari')
                            ->options(Empleat::query()->pluck('nom_complet', 'id'))
                            ->searchable()
                            ->required()
                            ->disabled(fn ($record) => $record && $record->exists)
                            ->helperText('Selecciona l\'empleat que necessita els accessos'),
                            
                        Select::make('usuari_solicitant_id')
                            ->label('Usuari Sol·licitant')
                            ->options(User::query()->pluck('name', 'id'))
                            ->default(auth()->id())
                            ->disabled()
                            ->required(),
                            
                        Select::make('estat')
                            ->label('Estat')
                            ->options([
                                'pendent' => '⏳ Pendent',
                                'validant' => '🔄 Validant',
                                'aprovada' => '✅ Aprovada',
                                'rebutjada' => '❌ Rebutjada',
                                'finalitzada' => '🏁 Finalitzada',
                            ])
                            ->default('pendent')
                            ->required()
                            ->disabled(fn ($record) => $record && in_array($record->estat, ['aprovada', 'rebutjada', 'finalitzada'])),
                            
                        Textarea::make('justificacio')
                            ->label('Justificació')
                            ->required()
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->helperText('Descriu detalladament la justificació per a aquesta sol·licitud'),
                            
                        // Campo oculto para el identificador únic
                        TextInput::make('identificador_unic')
                            ->hidden()
                            ->default(fn () => 'SOL-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 8))),
                    ]),
                    
                // Sección para los sistemas solicitados
                Section::make('Sistemes Sol·licitats')
                    ->schema([
                        Repeater::make('sistemesSolicitats')
                            ->relationship()
                            ->schema([
                                Select::make('sistema_id')
                                    ->relationship('sistema', 'nom')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        $set('nivell_acces_id', null);
                                        // Reset elements extra quan canvia el sistema
                                        $set('elements_extra', []);
                                    })
                                    ->label('Sistema'),
                                    
                                // Mostrar informació del tipus de sistema
                                Placeholder::make('tipus_sistema_info')
                                    ->label('')
                                    ->content(function (callable $get) {
                                        $sistemaId = $get('sistema_id');
                                        if (!$sistemaId) {
                                            return '';
                                        }
                                        
                                        $sistema = Sistema::with('elementsExtra')->find($sistemaId);
                                        if (!$sistema) {
                                            return '';
                                        }
                                        
                                        $esHibrid = $sistema->teElementsComplexos();
                                        $tipus = $esHibrid ? 'Híbrid' : 'Simple';
                                        $icon = $esHibrid ? '🔧' : '📄';
                                        
                                        return "{$icon} Sistema {$tipus}";
                                    })
                                    ->visible(fn (callable $get) => !empty($get('sistema_id')))
                                    ->columnSpanFull(),
                                    
                                // Nivells d'accés (sempre visible)
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
                                    ->required()
                                    ->visible(fn (callable $get) => !empty($get('sistema_id'))),
                                    
                                // Elements extra (només per sistemes híbrids)
                                Fieldset::make('Elements Extra')
                                    ->schema([
                                        Repeater::make('elements_extra')
                                            ->schema([
                                                Select::make('element_extra_id')
                                                    ->label('Element')
                                                    ->options(function (callable $get) {
                                                        $sistemaId = $get('../../sistema_id');
                                                        if (!$sistemaId) {
                                                            return [];
                                                        }
                                                        
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
                                                        if (!$elementId) {
                                                            return [];
                                                        }
                                                        
                                                        $element = SistemaElementExtra::find($elementId);
                                                        if (!$element || !$element->teOpcions()) {
                                                            return [];
                                                        }
                                                        
                                                        return array_combine($element->opcions_disponibles, $element->opcions_disponibles);
                                                    })
                                                    ->visible(function (callable $get) {
                                                        $elementId = $get('element_extra_id');
                                                        if (!$elementId) {
                                                            return false;
                                                        }
                                                        
                                                        $element = SistemaElementExtra::find($elementId);
                                                        return $element && $element->teOpcions();
                                                    }),
                                                    
                                                Textarea::make('valor_text_lliure')
                                                    ->label('Text Personalitzat')
                                                    ->rows(2)
                                                    ->visible(function (callable $get) {
                                                        $elementId = $get('element_extra_id');
                                                        if (!$elementId) {
                                                            return false;
                                                        }
                                                        
                                                        $element = SistemaElementExtra::find($elementId);
                                                        return $element && $element->acceptaTextLliure();
                                                    }),
                                            ])
                                            ->columns(1)
                                            ->itemLabel(function (array $state) {
                                                if (empty($state['element_extra_id'])) {
                                                    return 'Element Extra';
                                                }
                                                
                                                $element = SistemaElementExtra::find($state['element_extra_id']);
                                                return $element ? $element->nom : 'Element Extra';
                                            })
                                            ->addActionLabel('Afegir Element Extra')
                                            ->collapsible()
                                            ->reorderable(false)
                                            ->defaultItems(0),
                                    ])
                                    ->visible(function (callable $get) {
                                        $sistemaId = $get('sistema_id');
                                        if (!$sistemaId) {
                                            return false;
                                        }
                                        
                                        $sistema = Sistema::find($sistemaId);
                                        return $sistema && $sistema->teElementsComplexos();
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['sistema_id'] ?? null)
                            ->addActionLabel('Afegir Sistema')
                            ->minItems(1)
                            ->collapsible()
                            ->collapsed()
                            ->reorderable()
                            ->defaultItems(1),
                    ])
                    ->hiddenOn('edit'), // Solo mostrar en creación
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('identificador_unic')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                    
                TextColumn::make('empleatDestinatari.nom_complet')
                    ->label('Empleat')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('usuariSolicitant.name')
                    ->label('Sol·licitant')
                    ->searchable()
                    ->sortable(),
                    
                BadgeColumn::make('estat')
                    ->label('Estat')
                    ->colors([
                        'warning' => 'pendent',
                        'info' => 'validant',
                        'success' => 'aprovada',
                        'danger' => 'rebutjada',
                        'gray' => 'finalitzada',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pendent' => '⏳ Pendent',
                        'validant' => '🔄 Validant',
                        'aprovada' => '✅ Aprovada',
                        'rebutjada' => '❌ Rebutjada',
                        'finalitzada' => '🏁 Finalitzada',
                        default => $state,
                    })
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('Data de creació')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('sistemesSolicitats_count')
                    ->label('Sistemes')
                    ->counts('sistemesSolicitats')
                    ->alignCenter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estat')
                    ->label('Filtrar per estat')
                    ->options([
                        'pendent' => '⏳ Pendent',
                        'validant' => '🔄 Validant',
                        'aprovada' => '✅ Aprovada',
                        'rebutjada' => '❌ Rebutjada',
                        'finalitzada' => '🏁 Finalitzada',
                    ]),
                    
                SelectFilter::make('empleat_id')
                    ->label('Filtrar per empleat/da')
                    ->relationship('empleatDestinatari', 'nom_complet')
                    ->searchable()
                    ->preload(),
                    
                Filter::make('meves_sollicituds')
                    ->label('Les meves sol·licituds')
                    ->query(fn (Builder $query): Builder => $query->where('usuari_solicitant_id', auth()->id()))
                    ->default(),
                    
                Filter::make('pendents_meves_validacions')
                    ->label('Pendents de la meva validació')
                    ->query(fn (Builder $query): Builder => $query->whereHas('validacions', function($q) {
                        $q->where('usuari_validador_id', auth()->id())
                          ->where('estat', 'pendent');
                    })),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Veure detalls'),
                    
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar')
                    ->visible(fn (SolicitudAcces $record): bool => 
                        in_array($record->estat, ['pendent', 'validant']) && 
                        (auth()->user()->hasRole('admin') || $record->usuari_solicitant_id === auth()->id())
                    ),
                    
                Tables\Actions\Action::make('aprovar')
                    ->label('')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprovar sol·licitud')
                    ->modalDescription('Estàs segur que vols aprovar aquesta sol·licitud?')
                    ->action(fn (SolicitudAcces $record) => $record->update(['estat' => 'aprovada']))
                    ->visible(fn (SolicitudAcces $record): bool => 
                        in_array($record->estat, ['pendent', 'validant']) && 
                        auth()->user()->hasRole('admin')
                    )
                    ->tooltip('Aprovar sol·licitud'),
                    
                Tables\Actions\Action::make('rebutjar')
                    ->label('')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rebutjar sol·licitud')
                    ->modalDescription('Estàs segur que vols rebutjar aquesta sol·licitud?')
                    ->form([
                        Textarea::make('rao_rebuig')
                            ->label('Raó del rebuig')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (SolicitudAcces $record, array $data): void {
                        $record->update([
                            'estat' => 'rebutjada',
                            'justificacio' => $record->justificacio . "\n\n---\n**Raó del rebuig:** " . $data['rao_rebuig']
                        ]);
                    })
                    ->visible(fn (SolicitudAcces $record): bool => 
                        in_array($record->estat, ['pendent', 'validant']) && 
                        auth()->user()->hasRole('admin')
                    )
                    ->tooltip('Rebutjar sol·licitud'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn (): bool => !auth()->user()->hasRole('admin')),
                ]),
            ]);
    }
    public static function getRelations(): array
    {
        return [
            RelationManagers\SolicitudSistemesRelationManager::class,
            RelationManagers\ValidacionsRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolicitudAcces::route('/'),
            'create' => Pages\CreateSolicitudAcces::route('/create'),
            'edit' => Pages\EditSolicitudAcces::route('/{record}/edit'),
        ];
    }
}
