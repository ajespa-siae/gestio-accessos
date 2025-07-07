<?php

namespace App\Filament\Operatiu\Resources\SolicitudAccesResource\Pages;

use App\Filament\Operatiu\Resources\SolicitudAccesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolicitudAcces extends ListRecords
{
    protected static string $resource = SolicitudAccesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
