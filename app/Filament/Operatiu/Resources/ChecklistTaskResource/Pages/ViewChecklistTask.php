<?php

namespace App\Filament\Operatiu\Resources\ChecklistTaskResource\Pages;

use App\Filament\Operatiu\Resources\ChecklistTaskResource;
use App\Models\ChecklistTask;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewChecklistTask extends ViewRecord
{
    protected static string $resource = ChecklistTaskResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            // Acción para asignar usuario
            Actions\Action::make('assignar_usuari')
                ->label('Assignar usuari')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->visible(function (ChecklistTask $record) {
                    return !$record->usuari_assignat_id && 
                        (auth()->user()->hasRole('admin') || 
                         auth()->user()->hasRole($record->rol_assignat));
                })
                ->form([
                    Select::make('usuari_assignat_id')
                        ->label('Assignar a usuari')
                        ->options(function (ChecklistTask $record) {
                            return User::query()
                                ->where('actiu', true)
                                ->whereHas('roles', function ($query) use ($record) {
                                    $query->where('name', $record->rol_assignat);
                                })
                                ->orWhere('rol_principal', $record->rol_assignat)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data, ChecklistTask $record) {
                    $record->update([
                        'usuari_assignat_id' => $data['usuari_assignat_id'],
                        'data_assignacio' => now(),
                    ]);
                    
                    // Notificar al usuario asignado
                    \App\Jobs\NotificarTascaAssignada::dispatch($record);
                    
                    Notification::make()
                        ->title('Tasca assignada')
                        ->success()
                        ->send();
                }),
                
            // Acción para desasignar usuario
            Actions\Action::make('desassignar_usuari')
                ->label('Desassignar usuari')
                ->icon('heroicon-o-user-minus')
                ->color('warning')
                ->visible(function (ChecklistTask $record) {
                    return $record->usuari_assignat_id && 
                        !$record->completada &&
                        (auth()->user()->hasRole('admin') || 
                         auth()->user()->hasRole($record->rol_assignat));
                })
                ->requiresConfirmation()
                ->modalHeading('Desassignar usuari')
                ->modalDescription('Estàs segur que vols desassignar l\'usuari d\'aquesta tasca?')
                ->action(function (ChecklistTask $record) {
                    // La columna data_assignacio no puede ser nula, establecemos una fecha por defecto
                    $record->update([
                        'usuari_assignat_id' => null,
                        'data_assignacio' => now(), // Usamos la fecha actual como valor por defecto
                    ]);
                    
                    Notification::make()
                        ->title('Usuari desassignat')
                        ->success()
                        ->send();
                }),
                
            // Acción para completar tarea
            Actions\Action::make('completar')
                ->label('Completar tasca')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(function (ChecklistTask $record) {
                    return !$record->completada && 
                        (auth()->user()->hasRole('admin') || 
                         auth()->user()->hasRole($record->rol_assignat) || 
                         $record->usuari_assignat_id === auth()->id());
                })
                ->requiresConfirmation()
                ->modalHeading('Completar tasca')
                ->modalDescription('Estàs segur que vols marcar aquesta tasca com a completada?')
                ->form([
                    Textarea::make('observacions')
                        ->label('Observacions (opcional)')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data, ChecklistTask $record) {
                    $record->completar(auth()->user(), $data['observacions'] ?? null);
                    
                    Notification::make()
                        ->title('Tasca completada')
                        ->success()
                        ->send();
                }),
                
            // Mantener el botón de editar solo para administradores
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()->hasRole('admin')),
        ];
    }
}
