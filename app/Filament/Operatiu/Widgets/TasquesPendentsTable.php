<?php

namespace App\Filament\Operatiu\Widgets;

use App\Models\ChecklistTask;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class TasquesPendentsTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $query = ChecklistTask::with(['checklistInstance.empleat', 'usuariAssignat', 'usuariCompletat'])
            ->where('completada', false)
            ->orderBy('data_limit')
            ->limit(5);

        if (!$user->hasRole('admin')) {
            $query->where('usuari_assignat_id', $user->id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('tasca')
                    ->label('Tasca')
                    ->searchable()
                    ->limit(50),
                    
                Tables\Columns\TextColumn::make('checklistInstance.empleat.nom_complet')
                    ->label('Empleat')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('data_limit')
                    ->label('Data límit')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->data_limit < now() ? 'danger' : 'default'),
                    
                Tables\Columns\TextColumn::make('usuariAssignat.name')
                    ->label('Assignada a')
                    ->searchable()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('completar')
                    ->label('Completar')
                    ->icon('heroicon-o-check')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('observacions')
                            ->label('Observacions')
                            ->maxLength(500),
                    ])
                    ->action(function (array $data, $record): void {
                        $record->completar(
                            Auth::user(),
                            $data['observacions'] ?? null
                        );
                    })
                    ->visible(fn ($record) => 
                        $record->usuari_assignat_id === Auth::id() || 
                        Auth::user()->hasRole('admin')
                    ),
            ]);
    }

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'it']);
    }
}
