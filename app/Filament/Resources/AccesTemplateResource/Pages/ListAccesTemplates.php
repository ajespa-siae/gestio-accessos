<?php

namespace App\Filament\Resources\AccesTemplateResource\Pages;

use App\Filament\Resources\AccesTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccesTemplates extends ListRecords
{
    protected static string $resource = AccesTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Plantilla'),
        ];
    }
}
