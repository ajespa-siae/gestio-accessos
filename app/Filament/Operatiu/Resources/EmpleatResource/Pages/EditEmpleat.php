<?php

namespace App\Filament\Operatiu\Resources\EmpleatResource\Pages;

use App\Filament\Operatiu\Resources\EmpleatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmpleat extends EditRecord
{
    protected static string $resource = EmpleatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
