<?php

namespace App\Filament\Resources\ChecklistTemplateResource\Pages;

use App\Filament\Resources\ChecklistTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChecklistTemplate extends CreateRecord
{
    protected static string $resource = ChecklistTemplateResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
    
    protected function afterCreate(): void
    {
        \Filament\Notifications\Notification::make()
            ->title('Plantilla creada correctament')
            ->body('Ara pots afegir tasques des de la pestanya "Tasques Template"')
            ->success()
            ->send();
    }
}
