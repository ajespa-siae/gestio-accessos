<?php

namespace App\Filament\Resources\ChecklistTemplateResource\Pages;

use App\Filament\Resources\ChecklistTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChecklistTemplate extends EditRecord
{
    protected static string $resource = ChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function afterSave(): void
    {
        \Filament\Notifications\Notification::make()
            ->title('Plantilla actualitzada correctament')
            ->success()
            ->send();
    }
}