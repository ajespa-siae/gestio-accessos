<?php
// app/Filament/Resources/SistemaResource/RelationManagers/NivellsAccesRelationManager.php

namespace App\Filament\Resources\SistemaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class NivellsAccesRelationManager extends RelationManager
{
    protected static string $relationship = 'nivellsAcces';

    protected static ?string $title = 'Nivells d\'Accés';

    protected static ?string $modelLabel = 'Nivell d\'Accés';

    protected static ?string $pluralModelLabel = 'Nivells d\'Accés';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuració del Nivell')
                    ->schema([
                        TextInput::make('nom')
                            ->required()
                            ->maxLength(255)
                            ->label('Nom del Nivell')
                            ->helperText('Ex: Consulta, Gestió, Supervisor, Administrador')
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                return $rule->where('sistema_id', $this->getOwnerRecord()->id);
                            }),
                            
                        Textarea::make('descripcio')
                            ->maxLength(1000)
                            ->label('Descripció')
                            ->helperText('Descriure què pot fer l\'usuari amb aquest nivell d\'accés')
                            ->columnSpanFull(),
                            
                        TextInput::make('ordre')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(99)
                            ->default(function () {
                                $ultimOrdre = $this->getOwnerRecord()
                                    ->nivellsAcces()
                                    ->max('ordre');
                                return ($ultimOrdre ?? 0) + 1;
                            })
                            ->label('Ordre')
                            ->helperText('Ordre d\'aparició en les sol·licituds (1 = primer)'),
                            
                        Toggle::make('actiu')
                            ->default(true)
                            ->label('Nivell Actiu')
                            ->helperText('Els nivells inactius no apareixeran en noves sol·licituds'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            // Corregido: se estaba seleccionando departaments.* incorrectamente
            ->modifyQueryUsing(fn (Builder $query) => $query->select('nivells_acces_sistema.*'))
            ->columns([
                TextColumn::make('ordre')
                    ->label('Ordre')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                    
                TextColumn::make('nom')
                    ->label('Nom del Nivell')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('descripcio')
                    ->label('Descripció')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->placeholder('Sense descripció'),
                    
                IconColumn::make('actiu')
                    ->boolean()
                    ->label('Actiu'),
                    
                TextColumn::make('solicituds_count')
                    ->label('Sol·licituds')
                    ->getStateUsing(function ($record) {
                        return $record->solicituds()->count();
                    })
                    ->badge()
                    ->color('info'),
                    
                TextColumn::make('solicituds_pendents')
                    ->label('Pendents')
                    ->getStateUsing(function ($record) {
                        return $record->solicituds()
                            ->whereHas('solicitud', fn ($q) => $q->whereIn('estat', ['pendent', 'validant']))
                            ->count();
                    })
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),
                    
                TextColumn::make('created_at')
                    ->label('Creat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\Filter::make('actius')
                    ->label('Només actius')
                    ->query(fn (Builder $query): Builder => $query->where('actiu', true))
                    ->default(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nou Nivell')
                    ->icon('heroicon-o-plus')
                    ->successNotificationTitle('Nivell d\'accés creat correctament'),
                    
                Action::make('reordenar')
                    ->label('Reordenar Nivells')
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('gray')
                    ->form([
                        Forms\Components\Repeater::make('nivells')
                            ->label('Reordenar nivells d\'accés')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\TextInput::make('nom')
                                    ->disabled(),
                                Forms\Components\TextInput::make('ordre')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),
                            ])
                            ->defaultItems(0)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ])
                    ->fillForm(function (): array {
                        return [
                            'nivells' => $this->getOwnerRecord()
                                ->nivellsAcces()
                                ->orderBy('ordre')
                                ->get()
                                ->map(fn ($nivell) => [
                                    'id' => $nivell->id,
                                    'nom' => $nivell->nom,
                                    'ordre' => $nivell->ordre,
                                ])
                                ->toArray(),
                        ];
                    })
                    ->action(function (array $data): void {
                        foreach ($data['nivells'] as $nivellData) {
                            $this->getOwnerRecord()
                                ->nivellsAcces()
                                ->where('id', $nivellData['id'])
                                ->update(['ordre' => $nivellData['ordre']]);
                        }
                        
                        Notification::make()
                            ->title('Nivells reordenats correctament')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->successNotificationTitle('Nivell actualitzat correctament'),
                    
                Action::make('duplicar')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->form([
                        TextInput::make('nou_nom')
                            ->label('Nom del nou nivell')
                            ->required()
                            ->helperText('El nou nivell es crearà amb la mateixa configuració'),
                    ])
                    ->action(function ($record, array $data): void {
                        $nouNivell = $record->replicate();
                        $nouNivell->nom = $data['nou_nom'];
                        $nouNivell->ordre = $this->getOwnerRecord()->nivellsAcces()->max('ordre') + 1;
                        $nouNivell->save();
                        
                        Notification::make()
                            ->title('Nivell duplicat correctament')
                            ->success()
                            ->send();
                    }),
                    
                Action::make('veure_solicituds')
                    ->label('Veure Sol·licituds')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(fn ($record) => $record->solicituds()->exists())
                    ->url(fn ($record) => 
                        route('filament.admin.resources.solicitud-sistemes.index', [
                            'tableFilters[nivell_acces_id][value]' => $record->id
                        ])
                    ),
                    
                Action::make('activar_desactivar')
                    ->label(fn ($record) => $record->actiu ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->actiu ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($record) => $record->actiu ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $record->update(['actiu' => !$record->actiu]);
                        
                        $estat = $record->actiu ? 'activat' : 'desactivat';
                        Notification::make()
                            ->title("Nivell {$estat}")
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Atenció: Aquesta acció eliminarà permanentment el nivell d\'accés. Les sol·licituds existents mantindran la referència però no es podran crear noves sol·licituds amb aquest nivell.')
                    ->before(function ($record) {
                        // Verificar si hi ha sol·licituds actives
                        $solicitudsPendents = $record->solicituds()
                            ->whereHas('solicitud', fn ($q) => $q->whereIn('estat', ['pendent', 'validant', 'aprovada']))
                            ->count();
                            
                        if ($solicitudsPendents > 0) {
                            Notification::make()
                                ->title('No es pot eliminar')
                                ->body("Aquest nivell té {$solicitudsPendents} sol·licituds actives. Desactiveu-lo en lloc d'eliminar-lo.")
                                ->danger()
                                ->send();
                                
                            return false;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                        
                    Tables\Actions\BulkAction::make('activar')
                        ->label('Activar')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['actiu' => true]);
                            
                            Notification::make()
                                ->title('Nivells activats')
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\BulkAction::make('desactivar')
                        ->label('Desactivar')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['actiu' => false]);
                            
                            Notification::make()
                                ->title('Nivells desactivats')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('ordre')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}