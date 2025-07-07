<?php

namespace App\Filament\Operatiu\Resources\SolicitudAccesResource\Pages;

use App\Filament\Operatiu\Resources\SolicitudAccesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolicitudAcces extends EditRecord
{
    protected static string $resource = SolicitudAccesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
