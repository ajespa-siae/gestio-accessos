<?php

namespace App\Filament\Operatiu\Resources\ProcessMobilitatGestorResource\Pages;

use App\Filament\Operatiu\Resources\ProcessMobilitatGestorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcessMobilitatGestor extends EditRecord
{
    protected static string $resource = ProcessMobilitatGestorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
