<?php

// app/Filament/Resources/DepartamentResource/Pages/ViewDepartament.php

namespace App\Filament\Resources\DepartamentResource\Pages;

use App\Filament\Resources\DepartamentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDepartament extends ViewRecord
{
    protected static string $resource = DepartamentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}