<?php
// app/Filament/Resources/SistemaResource/RelationManagers/DepartamentsRelationManager.php

namespace App\Filament\Resources\SistemaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use App\Models\Departament;
use Illuminate\Database\Eloquent\Builder;

class DepartamentsRelationManager extends RelationManager
{
    protected static string $relationship = 'departaments';
    
    // Modificar la consulta de relación para evitar DISTINCT en campos JSON
    public function getRelationship(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return parent::getRelationship()->select('departaments.id', 'departaments.nom', 'departaments.descripcio', 'departaments.gestor_id', 'departaments.actiu', 'departaments.data_creacio', 'departaments.created_at', 'departaments.updated_at');
    }

    protected static ?string $title = 'Departaments Autoritzats';

    protected static ?string $modelLabel = 'Departament';

    protected static ?string $pluralModelLabel = 'Departaments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('departament_id')
                    ->label('Departament')
                    ->options(function () {
                        // Només mostrar departaments que no estan ja assignats
                        $assignats = $this->getOwnerRecord()->departaments()->pluck('departaments.id');
                        
                        // Evitar DISTINCT en campos JSON seleccionando explícitamente
                        return Departament::query()
                            ->select(['id', 'nom'])
                            ->where('actiu', true)
                            ->whereNotIn('id', $assignats)
                            ->get()
                            ->pluck('nom', 'id')
                            ->toArray();
                    })
                    ->required()
                    ->searchable()
                    ->helperText('Seleccioneu un departament que pugui sol·licitar accés a aquest sistema'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            ->columns([
                TextColumn::make('nom')
                    ->label('Nom del Departament')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('descripcio')
                    ->label('Descripció')
                    ->limit(40)
                    ->placeholder('Sense descripció'),
                    
                TextColumn::make('gestor.name')
                    ->label('Gestor')
                    ->placeholder('Sense gestor assignat'),
                    
                IconColumn::make('actiu')
                    ->boolean()
                    ->label('Actiu'),
                    
                TextColumn::make('empleats_count')
                    ->counts('empleats')
                    ->label('Empleats')
                    ->badge()
                    ->color('info'),
                    
                TextColumn::make('solicituds_sistema')
                    ->label('Sol·licituds del Sistema')
                    ->getStateUsing(function ($record) {
                        return $record->empleats()
                            ->whereHas('solicitudsAcces', function ($q) {
                                $q->whereHas('sistemesSolicitats', function ($sq) {
                                    $sq->where('sistema_id', $this->getOwnerRecord()->id);
                                });
                            })
                            ->count();
                    })
                    ->badge()
                    ->color('warning'),
                    
                TextColumn::make('pivot.created_at')
                    ->label('Autoritzat el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('actius')
                    ->label('Només departaments actius')
                    ->query(fn (Builder $query): Builder => $query->where('departaments.actiu', true))
                    ->default(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Autoritzar Departament')
                    ->icon('heroicon-o-plus')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Departament')
                            ->options(function () {
                                // Només mostrar departaments que no estan ja assignats
                                $assignats = $this->getOwnerRecord()->departaments()->pluck('departaments.id');
                                
                                return Departament::where('actiu', true)
                                    ->whereNotIn('id', $assignats)
                                    ->pluck('nom', 'id');
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->successNotificationTitle('Departament autoritzat correctament'),
                    
                Action::make('autoritzar_tots')
                    ->label('Autoritzar Tots els Departaments')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Això autoritzarà tots els departaments actius a sol·licitar accés a aquest sistema.')
                    ->action(function (): void {
                        $assignats = $this->getOwnerRecord()->departaments()->pluck('departaments.id');
                        $tots = Departament::where('actiu', true)->pluck('id');
                        $nous = $tots->diff($assignats);
                        
                        if ($nous->isNotEmpty()) {
                            $this->getOwnerRecord()->departaments()->attach($nous);
                            
                            Notification::make()
                                ->title('Departaments autoritzats')
                                ->body("S'han autoritzat {$nous->count()} departaments nous.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tots els departaments ja estan autoritzats')
                                ->info()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Action::make('veure_empleats')
                    ->label('Veure Empleats')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->url(fn ($record) => 
                        route('filament.admin.resources.empleats.index', [
                            'tableFilters[departament][value]' => $record->id
                        ])
                    ),
                    
                Action::make('veure_solicituds')
                    ->label('Sol·licituds del Sistema')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->visible(function ($record) {
                        return $record->empleats()
                            ->whereHas('solicitudsAcces', function ($q) {
                                $q->whereHas('sistemesSolicitats', function ($sq) {
                                    $sq->where('sistema_id', $this->getOwnerRecord()->id);
                                });
                            })
                            ->exists();
                    })
                    ->url(fn ($record) => 
                        route('filament.admin.resources.solicitud-acces.index', [
                            'tableFilters[departament][value]' => $record->id,
                            'tableFilters[sistema][value]' => $this->getOwnerRecord()->id
                        ])
                    ),
                    
                Tables\Actions\DetachAction::make()
                    ->label('Desautoritzar')
                    ->requiresConfirmation()
                    ->modalDescription('Aquest departament ja no podrà sol·licitar nous accessos a aquest sistema. Les sol·licituds existents no es veuran afectades.')
                    ->successNotificationTitle('Departament desautoritzat'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Desautoritzar Seleccionats')
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('Cap departament autoritzat')
            ->emptyStateDescription('Aquest sistema no té cap departament autoritzat específicament. Això significa que tots els departaments poden sol·licitar accés.')
            ->emptyStateIcon('heroicon-o-shield-exclamation')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}