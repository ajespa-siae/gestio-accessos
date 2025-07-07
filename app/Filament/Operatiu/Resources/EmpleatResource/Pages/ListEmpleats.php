<?php

namespace App\Filament\Operatiu\Resources\EmpleatResource\Pages;

use App\Filament\Operatiu\Resources\EmpleatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmpleats extends ListRecords
{
    protected static string $resource = EmpleatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
