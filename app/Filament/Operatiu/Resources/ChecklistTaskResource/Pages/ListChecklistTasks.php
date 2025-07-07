<?php

namespace App\Filament\Operatiu\Resources\ChecklistTaskResource\Pages;

use App\Filament\Operatiu\Resources\ChecklistTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChecklistTasks extends ListRecords
{
    protected static string $resource = ChecklistTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
