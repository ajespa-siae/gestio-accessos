<?php

namespace App\Filament\Operatiu\Resources;

use App\Filament\Operatiu\Resources\ProcessMobilitatGestorResource\Pages;
use App\Filament\Operatiu\Resources\ProcessMobilitatGestorResource\RelationManagers;
use App\Models\ProcessMobilitat;
use App\Models\Departament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProcessMobilitatGestorResource extends Resource
{
    protected static ?string $model = ProcessMobilitat::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Gestió de Sol·licituds';
    protected static ?string $modelLabel = 'Mobilitat per Revisar';
    protected static ?string $pluralModelLabel = 'Mobilitats per Revisar';
    protected static ?string $navigationLabel = 'Mobilitats';
    
    // Utilitza la policy per controlar l'accés
    // La policy ja controla que només gestors i admin puguin accedir
    
    // Filtrar només processos assignats al gestor
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        
        if (!$user->hasRole('admin')) {
            // Obtenir departaments del gestor
            $departaments = $user->departamentsGestionats->pluck('id');
            
            $query->where(function ($q) use ($departaments) {
                // Processos pendents del departament actual (si soc gestor d'aquest dept)
                $q->where(function ($subQ) use ($departaments) {
                    $subQ->where('estat', 'pendent_dept_actual')
                         ->whereIn('departament_actual_id', $departaments);
                })
                // O processos pendents del departament nou (si soc gestor d'aquest dept)
                ->orWhere(function ($subQ) use ($departaments) {
                    $subQ->where('estat', 'pendent_dept_nou')
                         ->whereIn('departament_nou_id', $departaments);
                });
            });
        }
        
        return $query;
    }

    public static function form(Form $form): Form
    {
        // Els gestors només poden veure, no editar
        return $form
            ->schema([
                Forms\Components\Placeholder::make('info')
                    ->content('Utilitza les accions de la taula per gestionar els processos de mobilitat.')
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
                    
                TextColumn::make('created_at')
                    ->label('Data Sol·licitud')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
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
                    ])
                    ->default(['pendent_dept_actual', 'pendent_dept_nou']),
            ])
            ->actions([
                Action::make('revisar')
                    ->label('Revisar')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (ProcessMobilitat $record): string => 
                        route('filament.operatiu.resources.process-mobilitat-gestors.revisar', $record)
                    )
                    ->visible(function (ProcessMobilitat $record): bool {
                        $user = Auth::user();
                        
                        // Admin pot veure tot
                        if ($user->hasRole('admin')) {
                            return $record->estat === 'pendent_dept_actual' || $record->estat === 'pendent_dept_nou';
                        }
                        
                        $departaments = $user->departamentsGestionats->pluck('id');
                        
                        // Verificar si pot revisar segons l'estat i departament
                        if ($record->estat === 'pendent_dept_actual') {
                            return $departaments->contains($record->departament_actual_id);
                        }
                        
                        if ($record->estat === 'pendent_dept_nou') {
                            return $departaments->contains($record->departament_nou_id);
                        }
                        
                        return false;
                    }),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListProcessMobilitatGestors::route('/'),
            'revisar' => Pages\RevisarProcessMobilitatGestor::route('/{record}/revisar'),
        ];
    }
}
