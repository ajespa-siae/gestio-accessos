<?php

namespace App\Filament\Resources\EmpleatResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
// ProgressColumn no está disponible en esta versión de Filament
// use Filament\Tables\Columns\ProgressColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChecklistsRelationManager extends RelationManager
{
    protected static string $relationship = 'checklists';

    protected static ?string $title = 'Checklists';

    protected static ?string $modelLabel = 'Checklist';

    protected static ?string $pluralModelLabel = 'Checklists';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Aquest RelationManager és només de lectura
                // La creació es fa automàticament via Jobs
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('template.nom')
            ->columns([
                TextColumn::make('template.nom')
                    ->label('Tipus de Checklist')
                    ->sortable()
                    ->searchable(),
                    
                BadgeColumn::make('template.tipus')
                    ->label('Categoria')
                    ->colors([
                        'success' => 'onboarding',
                        'danger' => 'offboarding',
                        'warning' => 'altres'
                    ]),
                    
                BadgeColumn::make('estat')
                    ->label('Estat')
                    ->colors([
                        'warning' => 'pendent',
                        'primary' => 'en_progres',
                        'success' => 'completada',
                        'danger' => 'cancel·lada'
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pendent',
                        'heroicon-o-arrow-path' => 'en_progres',
                        'heroicon-o-check-circle' => 'completada',
                        'heroicon-o-x-circle' => 'cancel·lada'
                    ]),
                    
                // Reemplazamos ProgressColumn por TextColumn con formato de porcentaje
                TextColumn::make('progress_percentatge')
                    ->label('Progrés')
                    ->getStateUsing(function ($record) {
                        $total = $record->tasques()->count();
                        $completades = $record->tasques()->where('completada', true)->count();
                        $percentatge = $total > 0 ? round(($completades / $total) * 100) : 0;
                        return "{$percentatge}%";
                    })
                    ->badge()
                    ->color(function ($state) {
                        $percentatge = (int)str_replace('%', '', $state);
                        if ($percentatge >= 100) return 'success';
                        if ($percentatge > 0) return 'warning';
                        return 'gray';
                    }),
                    
                TextColumn::make('tasques_completades')
                    ->label('Tasques')
                    ->getStateUsing(function ($record) {
                        $total = $record->tasques()->count();
                        $completades = $record->tasques()->where('completada', true)->count();
                        return "{$completades}/{$total}";
                    })
                    ->badge()
                    ->color(function ($state) {
                        [$completades, $total] = explode('/', $state);
                        if ($completades == $total) return 'success';
                        if ($completades > 0) return 'warning';
                        return 'gray';
                    }),
                    
                TextColumn::make('data_creacio')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('data_finalitzacio')
                    ->label('Finalitzada')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('estat')
                    ->options([
                        'pendent' => 'Pendent',
                        'en_progres' => 'En Progrés',
                        'completada' => 'Completada',
                        'cancel·lada' => 'Cancel·lada'
                    ]),
                    
                SelectFilter::make('tipus')
                    ->relationship('template', 'tipus')
                    ->options([
                        'onboarding' => 'Onboarding',
                        'offboarding' => 'Offboarding'
                    ]),
            ])
            ->headerActions([
                // No permetem crear manualment - es creen via automatismes
            ])
            ->actions([
                Action::make('veure_tasques')
                    ->label('Veure Tasques')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->url(fn ($record) => 
                        route('filament.admin.resources.checklist-tasks.index', [
                            'tableFilters[checklist_instance_id][value]' => $record->id
                        ])
                    ),
                    
                Action::make('marcar_completada')
                    ->label('Marcar Completada')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->estat !== 'completada')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'estat' => 'completada',
                            'data_finalitzacio' => now()
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Checklist marcada com completada')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\ViewAction::make()
                    ->visible(false), // Desactivem per ara
            ])
            ->bulkActions([
                // No permetem accions en massa per seguretat
            ])
            ->defaultSort('data_creacio', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}