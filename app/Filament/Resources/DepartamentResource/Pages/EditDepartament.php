<?php

namespace App\Filament\Resources\DepartamentResource\Pages;

use App\Filament\Resources\DepartamentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepartament extends EditRecord
{
    protected static string $resource = DepartamentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    $departament = $this->getRecord();
                    
                    // Verificar que no tingui empleats
                    $empleats = $departament->empleats()->count();
                    if ($empleats > 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('No es pot eliminar')
                            ->body("Aquest departament té {$empleats} empleats assignats.")
                            ->danger()
                            ->send();
                        
                        $this->halt();
                    }
                    
                    // Verificar que no sigui validador de sistemes
                    $sistemesValidador = \App\Models\SistemaValidador::where('departament_validador_id', $departament->id)
                        ->count();
                    
                    if ($sistemesValidador > 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('No es pot eliminar')
                            ->body("Aquest departament és validador de {$sistemesValidador} sistemes.")
                            ->danger()
                            ->send();
                        
                        $this->halt();
                    }
                }),
            
            Actions\Action::make('resum_gestors')
                ->label('Resum Gestors')
                ->icon('heroicon-o-users')
                ->color('info')
                ->modalContent(function () {
                    $departament = $this->getRecord();
                    $gestors = $departament->gestors;
                    
                    if ($gestors->isEmpty()) {
                        return new \Illuminate\Support\HtmlString(
                            '<div class="text-center p-6">
                                <p class="text-red-600 text-lg">❌ No hi ha gestors assignats</p>
                                <p class="text-gray-600 mt-2">Aquest departament necessita almenys un gestor per validar sol·licituds.</p>
                            </div>'
                        );
                    }
                    
                    $html = '<div class="space-y-4">';
                    $html .= '<h3 class="text-lg font-semibold">Gestors del Departament</h3>';
                    
                    foreach ($gestors as $gestor) {
                        $principal = $gestor->pivot->gestor_principal ? '⭐ Principal' : 'Gestor';
                        $actiu = $gestor->actiu ? '✅' : '❌ Inactiu';
                        
                        $html .= '<div class="border rounded-lg p-4">';
                        $html .= "<div class='flex justify-between items-start'>";
                        $html .= "<div>";
                        $html .= "<p class='font-medium'>{$gestor->name}</p>";
                        $html .= "<p class='text-sm text-gray-600'>{$gestor->email}</p>";
                        $html .= "</div>";
                        $html .= "<div class='text-right'>";
                        $html .= "<span class='block text-sm text-blue-600'>{$principal}</span>";
                        $html .= "<span class='block text-sm'>{$actiu}</span>";
                        $html .= "</div>";
                        $html .= "</div>";
                        $html .= "</div>";
                    }
                    
                    $html .= '</div>';
                    
                    return new \Illuminate\Support\HtmlString($html);
                })
                ->modalHeading('Gestors del Departament')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tancar'),
            
            Actions\Action::make('verificar_validacions')
                ->label('Verificar Validacions')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->action(function () {
                    $departament = $this->getRecord();
                    
                    // Buscar sistemes que usen aquest departament com a validador
                    $sistemesValidador = \App\Models\SistemaValidador::where('departament_validador_id', $departament->id)
                        ->where('actiu', true)
                        ->with('sistema')
                        ->get();
                    
                    if ($sistemesValidador->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->title('✅ No és validador de cap sistema')
                            ->success()
                            ->send();
                        return;
                    }
                    
                    $gestorsActius = $departament->getGestorsActius();
                    
                    if ($gestorsActius->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->title('⚠️ Problema detectat')
                            ->body("Aquest departament és validador de {$sistemesValidador->count()} sistemes però no té gestors actius assignats.")
                            ->warning()
                            ->persistent()
                            ->send();
                    } else {
                        $sistemes = $sistemesValidador->pluck('sistema.nom')->implode(', ');
                        \Filament\Notifications\Notification::make()
                            ->title('✅ Validacions correctes')
                            ->body("Aquest departament valida: {$sistemes}")
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
    
    protected function afterSave(): void
    {
        \Filament\Notifications\Notification::make()
            ->title('Departament actualitzat')
            ->success()
            ->send();
    }
}