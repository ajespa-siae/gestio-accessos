<?php

namespace App\Filament\Resources\ChecklistTemplateResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class TasquesTemplateRelationManager extends RelationManager
{
    protected static string $relationship = 'tasquesTemplate';
    
    protected static ?string $title = 'Tasques de la Plantilla';
    
    protected static ?string $modelLabel = 'Tasca';
    
    protected static ?string $pluralModelLabel = 'Tasques';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('nom')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Crear usuari LDAP')
                            ->columnSpan(1),
                        
                        TextInput::make('ordre')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(function () {
                                $maxOrdre = $this->getOwnerRecord()
                                    ->tasquesTemplate()
                                    ->max('ordre');
                                return ($maxOrdre ?? 0) + 1;
                            })
                            ->columnSpan(1),
                    ]),
                
                Textarea::make('descripcio')
                    ->placeholder('Descripció detallada de la tasca...')
                    ->rows(3)
                    ->columnSpanFull(),
                
                Grid::make(3)
                    ->schema([
                        Select::make('rol_assignat')
                            ->label('Assignat a')
                            ->options([
                                'it' => 'IT',
                                'rrhh' => 'RRHH',
                                'gestor' => 'Gestor'
                            ])
                            ->default('it')
                            ->required(),
                        
                        TextInput::make('dies_limit')
                            ->label('Dies límit')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('7')
                            ->helperText('Dies disponibles per completar la tasca'),
                        
                        Grid::make(1)
                            ->schema([
                                Toggle::make('obligatoria')
                                    ->label('Tasca obligatòria')
                                    ->default(true)
                                    ->helperText('Les tasques obligatòries han de ser completades'),
                                
                                Toggle::make('activa')
                                    ->label('Tasca activa')
                                    ->default(true)
                                    ->helperText('Només les tasques actives s\'inclouran en les instàncies'),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            ->columns([
                TextColumn::make('ordre')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->width('80px'),
                
                TextColumn::make('nom')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                
                TextColumn::make('descripcio')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->placeholder('Sense descripció')
                    ->color('gray'),
                
                BadgeColumn::make('rol_assignat')
                    ->label('Assignat a')
                    ->colors([
                        'danger' => 'it',
                        'warning' => 'rrhh',
                        'success' => 'gestor',
                    ])
                    ->icons([
                        'heroicon-o-computer-desktop' => 'it',
                        'heroicon-o-users' => 'rrhh',
                        'heroicon-o-briefcase' => 'gestor',
                    ]),
                
                TextColumn::make('dies_limit')
                    ->label('Dies límit')
                    ->placeholder('Il·limitat')
                    ->suffix(' dies')
                    ->color('warning'),
                
                IconColumn::make('obligatoria')
                    ->label('Obligat.')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                
                IconColumn::make('activa')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('rol_assignat')
                    ->label('Assignat a')
                    ->options([
                        'it' => 'IT',
                        'rrhh' => 'RRHH',
                        'gestor' => 'Gestor'
                    ]),
                
                Filter::make('obligatories')
                    ->label('Només obligatòries')
                    ->query(fn (Builder $query): Builder => $query->where('obligatoria', true)),
                
                Filter::make('actives')
                    ->label('Només actives')
                    ->query(fn (Builder $query): Builder => $query->where('activa', true))
                    ->default(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nova Tasca')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                EditAction::make(),
                
                Action::make('move_up')
                    ->label('Pujar')
                    ->icon('heroicon-o-arrow-up')
                    ->color('info')
                    ->action(function ($record) {
                        $previousTask = $this->getOwnerRecord()
                            ->tasquesTemplate()
                            ->where('ordre', '<', $record->ordre)
                            ->orderBy('ordre', 'desc')
                            ->first();
                        
                        if ($previousTask) {
                            $tempOrdre = $record->ordre;
                            $record->update(['ordre' => $previousTask->ordre]);
                            $previousTask->update(['ordre' => $tempOrdre]);
                        }
                    })
                    ->visible(function ($record): bool {
                        return $this->getOwnerRecord()
                            ->tasquesTemplate()
                            ->where('ordre', '<', $record->ordre)
                            ->exists();
                    }),
                
                Action::make('move_down')
                    ->label('Baixar')
                    ->icon('heroicon-o-arrow-down')
                    ->color('info')
                    ->action(function ($record) {
                        $nextTask = $this->getOwnerRecord()
                            ->tasquesTemplate()
                            ->where('ordre', '>', $record->ordre)
                            ->orderBy('ordre', 'asc')
                            ->first();
                        
                        if ($nextTask) {
                            $tempOrdre = $record->ordre;
                            $record->update(['ordre' => $nextTask->ordre]);
                            $nextTask->update(['ordre' => $tempOrdre]);
                        }
                    })
                    ->visible(function ($record): bool {
                        return $this->getOwnerRecord()
                            ->tasquesTemplate()
                            ->where('ordre', '>', $record->ordre)
                            ->exists();
                    }),
                
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['activa' => true]);
                            }
                        }),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['activa' => false]);
                            }
                        }),
                    
                    Tables\Actions\BulkAction::make('change_role')
                        ->label('Canviar assignació')
                        ->icon('heroicon-o-user-group')
                        ->color('info')
                        ->form([
                            Select::make('rol_assignat')
                                ->label('Nou rol assignat')
                                ->options([
                                    'it' => 'IT',
                                    'rrhh' => 'RRHH',
                                    'gestor' => 'Gestor'
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['rol_assignat' => $data['rol_assignat']]);
                            }
                        }),
                ])
            ])
            ->defaultSort('ordre')
            ->reorderable('ordre')
            ->paginated(false);
    }
}