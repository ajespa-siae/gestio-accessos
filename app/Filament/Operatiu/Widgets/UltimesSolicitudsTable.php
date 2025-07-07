<?php

namespace App\Filament\Operatiu\Widgets;

use App\Models\SolicitudAcces;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class UltimesSolicitudsTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $query = SolicitudAcces::with(['empleatDestinatari', 'usuariSolicitant'])
            ->latest()
            ->limit(5);

        if (!$user->hasRole('admin')) {
            $query->where(function($q) use ($user) {
                $q->where('usuari_solicitant_id', $user->id)
                  ->orWhereHas('empleatDestinatari', function($q) use ($user) {
                      $q->where('user_id', $user->id);
                  });
            });
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('identificador_unic')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('empleatDestinatari.nom_complet')
                    ->label('Empleat')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('estat')
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
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data de creació')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Veure')
                    ->icon('heroicon-o-eye')
                    ->url(fn (SolicitudAcces $record): string => route('filament.operatiu.resources.solicitud-acces.edit', $record)),
            ]);
    }

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'gestor']);
    }
}
