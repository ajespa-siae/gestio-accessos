<?php

namespace App\Filament\Operatiu\Resources\ChecklistTaskResource\Pages;

use App\Filament\Operatiu\Resources\ChecklistTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChecklistTask extends EditRecord
{
    protected static string $resource = ChecklistTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
