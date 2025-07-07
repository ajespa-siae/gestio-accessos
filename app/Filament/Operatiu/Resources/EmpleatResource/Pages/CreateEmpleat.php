<?php

namespace App\Filament\Operatiu\Resources\EmpleatResource\Pages;

use App\Filament\Operatiu\Resources\EmpleatResource;
use App\Jobs\CrearChecklistOnboarding;
use App\Models\ChecklistTemplate;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateEmpleat extends CreateRecord
{
    protected static string $resource = EmpleatResource::class;
    
    protected function afterCreate(): void
    {
        // Obtener el ID de la plantilla seleccionada
        $templateId = $this->data['onboarding_template'] ?? null;
        
        if ($templateId) {
            // Verificar si la plantilla existe
            $template = ChecklistTemplate::find($templateId);
            
            if ($template && $template->actiu && $template->tipus === 'onboarding') {
                // Cancelar el job automático y crear uno nuevo con la plantilla seleccionada
                CrearChecklistOnboarding::dispatch($this->record, $templateId)
                    ->delay(now()->addSeconds(5)); // Pequeño retraso para asegurar que se complete la transacción
                
                Notification::make()
                    ->title('Onboarding iniciado')
                    ->body("Se ha iniciado el proceso de onboarding con la plantilla: {$template->nom}")
                    ->success()
                    ->send();
            }
        }
    }
}
