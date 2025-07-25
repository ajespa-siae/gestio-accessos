<?php

namespace App\Filament\Operatiu\Resources\ProcessMobilitatResource\Pages;

use App\Filament\Operatiu\Resources\ProcessMobilitatResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProcessMobilitat extends ViewRecord
{
    protected static string $resource = ProcessMobilitatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
