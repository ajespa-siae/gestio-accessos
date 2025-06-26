<?php

// app/Filament/Resources/EmpleatResource/RelationManagers/ChecklistsRelationManager.php

namespace App\Filament\Resources\EmpleatResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\ChecklistInstance;
use App\Models\ChecklistTemplate;
use Illuminate\Database\Eloquent\Builder;

class ChecklistsRelationManager extends RelationManager
{
    protected static string $relationship = 'checklists';
    protected static ?string $title = 'Checklists';
    protected static ?string $recordTitleAttribute = 'template.nom';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('template_id')
                    ->relationship('template', 'nom')
                    ->required()
                    ->label('Template')
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\Select::make('estat')
                    ->options([
                        'pendent' => 'Pendent',
                        'en_progres' => 'En Progrés',
                        'completada' => 'Completada',
                    ])
                    ->required()
                    ->label('Estat'),
                    
                Forms\Components\DateTimePicker::make('data_finalitzacio')
                    ->label('Data Finalització')
                    ->visible(fn (Forms\Get $get) => $get('estat') === 'completada'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('template.nom')
            ->columns([
                Tables\Columns\TextColumn::make('template.nom')
                    ->label('Template')
                    ->weight('bold')
                    ->limit(25),
                    
                Tables\Columns\BadgeColumn::make('template.tipus')
                    ->label('Tipus')
                    ->colors([
                        'success' => 'onboarding',
                        'danger' => 'offboarding',
                    ])
                    ->icons([
                        'heroicon-o-arrow-right-circle' => 'onboarding',
                        'heroicon-o-arrow-left-circle' => 'offboarding',
                    ]),
                    
                Tables\Columns\BadgeColumn::make('estat')
                    ->label('Estat')
                    ->colors([
                        'warning' => 'pendent',
                        'info' => 'en_progres', 
                        'success' => 'completada',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pendent',
                        'heroicon-o-cog-6-tooth' => 'en_progres',
                        'heroicon-o-check-circle' => 'completada',
                    ]),
                    
                Tables\Columns\TextColumn::make('progres')
                    ->label('Progrés')
                    ->getStateUsing(function (ChecklistInstance $record) {
                        $total = $record->tasques()->count();
                        $completades = $record->tasques()->where('completada', true)->count();
                        return $total > 0 ? round(($completades / $total) * 100) . '%' : '0%';
                    })
                    ->badge()
                    ->color(fn (string $state) => match(true) {
                        str_contains($state, '100%') => 'success',
                        (int)str_replace('%', '', $state) >= 50 => 'warning',
                        default => 'danger'
                    }),
                    
                // Columnes ocultes per defecte
                Tables\Columns\TextColumn::make('tasques_count')
                    ->counts('tasques')
                    ->label('Total')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('tasques_completades_count')
                    ->label('Completades')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn (ChecklistInstance $record) => 
                        $record->tasques()->where('completada', true)->count()
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('data_finalitzacio')
                    ->label('Finalitzada')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('Pendent')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estat')
                    ->options([
                        'pendent' => 'Pendent',
                        'en_progres' => 'En Progrés',
                        'completada' => 'Completada',
                    ]),
                    
                Tables\Filters\SelectFilter::make('tipus')
                    ->options([
                        'onboarding' => 'Onboarding',
                        'offboarding' => 'Offboarding',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('template', function ($q) use ($data) {
                                $q->where('tipus', $data['value']);
                            });
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova Checklist')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['empleat_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
                    
                Action::make('crear_onboarding')
                    ->label('Crear Onboarding')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn () => !$this->ownerRecord->teChecklistOnboarding())
                    ->requiresConfirmation()
                    ->modalHeading('Crear Checklist d\'Onboarding')
                    ->modalDescription('Es crearà automàticament una checklist d\'onboarding per aquest empleat.')
                    ->action(function () {
                        \App\Jobs\CrearChecklistOnboarding::dispatch($this->ownerRecord);
                        
                        Notification::make()
                            ->title('Checklist d\'onboarding creada')
                            ->body('S\'ha creat automàticament la checklist d\'onboarding.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Veure Tasques'),
                    
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                    
                Action::make('gestionar_tasques')
                    ->label('Gestionar Tasques')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->form([
                        Forms\Components\Placeholder::make('info')
                            ->label('Informació')
                            ->content(fn (ChecklistInstance $record) => 
                                "Checklist: {$record->template->nom} - " .
                                "Tasques: {$record->tasques()->count()} total, " .
                                "{$record->tasques()->where('completada', true)->count()} completades"
                            ),
                        Forms\Components\Repeater::make('tasques_info')
                            ->label('Tasques')
                            ->schema([
                                Forms\Components\TextInput::make('nom')
                                    ->disabled(),
                                Forms\Components\Toggle::make('completada')
                                    ->disabled(),
                                Forms\Components\TextInput::make('usuari_assignat.name')
                                    ->label('Assignat a')
                                    ->disabled(),
                            ])
                            ->disabled()
                            ->default(fn (ChecklistInstance $record) => 
                                $record->tasques()->with('usuariAssignat')->get()->toArray()
                            ),
                    ])
                    ->modalWidth('lg')
                    ->action(function () {
                        // No action needed, just viewing
                    }),
                    
                Action::make('completar_checklist')
                    ->label('Completar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ChecklistInstance $record) => $record->estat !== 'completada')
                    ->requiresConfirmation()
                    ->modalHeading('Completar Checklist')
                    ->modalDescription('Aquesta acció marcarà totes les tasques com a completades.')
                    ->action(function (ChecklistInstance $record) {
                        $record->tasques()->update(['completada' => true, 'data_completada' => now()]);
                        $record->update(['estat' => 'completada', 'data_finalitzacio' => now()]);
                        
                        Notification::make()
                            ->title('Checklist completada')
                            ->body('Totes les tasques han estat marcades com a completades.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}