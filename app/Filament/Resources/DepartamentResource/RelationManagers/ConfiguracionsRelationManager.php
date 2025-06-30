<?php

namespace App\Filament\Resources\DepartamentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\DepartamentConfiguracio;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;

class ConfiguracionsRelationManager extends RelationManager
{
    protected static string $relationship = 'configuracions';

    protected static ?string $title = 'Configuracions';

    protected static ?string $modelLabel = 'Configuració';

    protected static ?string $pluralModelLabel = 'Configuracions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuració')
                    ->schema([
                        Select::make('clau')
                            ->label('Clau')
                            ->options([
                                'onboarding_automatico' => 'Onboarding Automàtic',
                                'email_notificacions' => 'Email per Notificacions',
                                'checklist_onboarding_dies' => 'Dies per Completar Onboarding',
                                'checklist_offboarding_dies' => 'Dies per Completar Offboarding',
                                'notificar_gestor_onboarding' => 'Notificar Gestor en Onboarding',
                                'notificar_gestor_offboarding' => 'Notificar Gestor en Offboarding',
                                'aprovacio_automatica_accessos' => 'Aprovació Automàtica d\'Accessos',
                                'template_onboarding_personalitzat' => 'Template Onboarding Personalitzat',
                                'template_offboarding_personalitzat' => 'Template Offboarding Personalitzat',
                                'custom' => 'Configuració Personalitzada',
                            ])
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Auto-completar descripcions predefinides
                                $descripcions = [
                                    'onboarding_automatico' => 'Activar procés d\'onboarding automàtic quan es crea un empleat',
                                    'email_notificacions' => 'Adreça de correu per rebre notificacions del departament',
                                    'checklist_onboarding_dies' => 'Número de dies per completar les tasques d\'onboarding',
                                    'checklist_offboarding_dies' => 'Número de dies per completar les tasques d\'offboarding',
                                    'notificar_gestor_onboarding' => 'Notificar automàticament al gestor quan s\'inicia l\'onboarding',
                                    'notificar_gestor_offboarding' => 'Notificar automàticament al gestor quan s\'inicia l\'offboarding',
                                    'aprovacio_automatica_accessos' => 'Aprovar automàticament sol·licituds d\'accés per sistemes bàsics',
                                    'template_onboarding_personalitzat' => 'ID del template personalitzat per onboarding d\'aquest departament',
                                    'template_offboarding_personalitzat' => 'ID del template personalitzat per offboarding d\'aquest departament',
                                ];
                                
                                if (isset($descripcions[$state])) {
                                    $set('descripcio', $descripcions[$state]);
                                }
                            }),
                            
                        TextInput::make('clau_personalitzada')
                            ->label('Clau Personalitzada')
                            ->visible(fn ($get) => $get('clau') === 'custom')
                            ->required(fn ($get) => $get('clau') === 'custom')
                            ->maxLength(255)
                            ->helperText('Introdueix el nom de la configuració personalitzada'),
                            
                        TextInput::make('valor')
                            ->required()
                            ->maxLength(1000)
                            ->helperText(function ($get) {
                                $exemples = [
                                    'onboarding_automatico' => 'Exemple: true o false',
                                    'email_notificacions' => 'Exemple: gestor@departament.com',
                                    'checklist_onboarding_dies' => 'Exemple: 7',
                                    'checklist_offboarding_dies' => 'Exemple: 3',
                                    'notificar_gestor_onboarding' => 'Exemple: true o false',
                                    'notificar_gestor_offboarding' => 'Exemple: true o false',
                                    'aprovacio_automatica_accessos' => 'Exemple: true o false',
                                    'template_onboarding_personalitzat' => 'Exemple: 5',
                                    'template_offboarding_personalitzat' => 'Exemple: 8',
                                ];
                                
                                return $exemples[$get('clau')] ?? 'Introdueix el valor de la configuració';
                            }),
                            
                        Textarea::make('descripcio')
                            ->label('Descripció')
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('clau')
            ->columns([
                TextColumn::make('clau')
                    ->label('Configuració')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (string $state): string {
                        $labels = [
                            'onboarding_automatico' => 'Onboarding Automàtic',
                            'email_notificacions' => 'Email Notificacions',
                            'checklist_onboarding_dies' => 'Dies Onboarding',
                            'checklist_offboarding_dies' => 'Dies Offboarding',
                            'notificar_gestor_onboarding' => 'Notificar Gestor Onboarding',
                            'notificar_gestor_offboarding' => 'Notificar Gestor Offboarding',
                            'aprovacio_automatica_accessos' => 'Aprovació Automàtica',
                            'template_onboarding_personalitzat' => 'Template Onboarding',
                            'template_offboarding_personalitzat' => 'Template Offboarding',
                        ];
                        
                        return $labels[$state] ?? $state;
                    }),
                    
                TextColumn::make('valor')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        return $column->getState();
                    })
                    ->copyable()
                    ->copyMessage('Valor copiat!')
                    ->copyMessageDuration(1500),
                    
                TextColumn::make('descripcio')
                    ->limit(60)
                    ->tooltip(function (TextColumn $column): ?string {
                        return $column->getState();
                    })
                    ->placeholder('Sense descripció'),
                    
                TextColumn::make('updated_at')
                    ->label('Última Actualització')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('clau')
                    ->label('Tipus de Configuració')
                    ->options([
                        'onboarding_automatico' => 'Onboarding Automàtic',
                        'email_notificacions' => 'Email Notificacions',
                        'checklist_onboarding_dies' => 'Dies Onboarding',
                        'checklist_offboarding_dies' => 'Dies Offboarding',
                        'notificar_gestor_onboarding' => 'Notificar Gestor Onboarding',
                        'notificar_gestor_offboarding' => 'Notificar Gestor Offboarding',
                        'aprovacio_automatica_accessos' => 'Aprovació Automàtica',
                        'template_onboarding_personalitzat' => 'Template Onboarding',
                        'template_offboarding_personalitzat' => 'Template Offboarding',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalHeading('Afegir Nova Configuració')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Si és configuració personalitzada, usar la clau personalitzada
                        if ($data['clau'] === 'custom' && !empty($data['clau_personalitzada'])) {
                            $data['clau'] = $data['clau_personalitzada'];
                        }
                        unset($data['clau_personalitzada']);
                        
                        return $data;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                
                EditAction::make()
                    ->mutateFormDataUsing(function (DepartamentConfiguracio $record, array $data): array {
                        // Si és configuració personalitzada, mostrar la clau actual
                        $clausPredefiniides = [
                            'onboarding_automatico', 'email_notificacions', 'checklist_onboarding_dies',
                            'checklist_offboarding_dies', 'notificar_gestor_onboarding', 
                            'notificar_gestor_offboarding', 'aprovacio_automatica_accessos',
                            'template_onboarding_personalitzat', 'template_offboarding_personalitzat'
                        ];
                        
                        if (!in_array($record->clau, $clausPredefiniides)) {
                            $data['clau'] = 'custom';
                            $data['clau_personalitzada'] = $record->clau;
                        }
                        
                        return $data;
                    })
                    ->mutateRecordDataUsing(function (array $data): array {
                        // Igual que en create
                        if ($data['clau'] === 'custom' && !empty($data['clau_personalitzada'])) {
                            $data['clau'] = $data['clau_personalitzada'];
                        }
                        unset($data['clau_personalitzada']);
                        
                        return $data;
                    }),
                    
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Configuració')
                    ->modalDescription('Aquesta acció no es pot desfer i pot afectar el funcionament del departament.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('clau');
    }
}