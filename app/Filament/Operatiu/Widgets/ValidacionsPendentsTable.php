<?php

namespace App\Filament\Operatiu\Widgets;

use App\Models\Validacio;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class ValidacionsPendentsTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $userId = $user->id;
        
        $query = Validacio::with(['solicitud.empleatDestinatari', 'validador', 'sistema'])
            ->where('estat', 'pendent')
            ->where(function($query) use ($userId) {
                $query->where('validador_id', $userId)
                      ->orWhereJsonContains('grup_validadors_ids', (string) $userId);
            })
            ->orderBy('created_at')
            ->limit(5);

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('solicitud.identificador_unic')
                    ->label('Sol·licitud')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('solicitud.empleatDestinatari.nom_complet')
                    ->label('Empleat')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('sistema.nom')
                    ->label('Sistema')
                    ->searchable(),
                    
                Tables\Columns\BadgeColumn::make('tipus_validacio')
                    ->label('Tipus')
                    ->formatStateUsing(fn (string $state): string => 
                        $state === 'individual' ? '👤 Individual' : '👥 Grup'
                    )
                    ->colors([
                        'primary' => 'individual',
                        'info' => 'grup',
                    ]),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data de creació')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('aprovar')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprovar validació')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('observacions')
                            ->label('Observacions (opcionals)')
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data, $record): void {
                        $record->aprovar(
                            Auth::user(),
                            $data['observacions'] ?? null
                        );
                    }),
                    
                Tables\Actions\Action::make('rebutjar')
                    ->label('Rebutjar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rebutjar validació')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('observacions')
                            ->label('Raó del rebuig')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data, $record): void {
                        $record->rebutjar(
                            Auth::user(),
                            $data['observacions']
                        );
                    }),
            ]);
    }

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'gestor']);
    }
}
