<?php

namespace App\Filament\Operatiu\Resources\ValidacioResource\Pages;

use App\Filament\Operatiu\Resources\ValidacioResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateValidacio extends CreateRecord
{
    protected static string $resource = ValidacioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['validador_id'] = $data['validador_id'] ?? auth()->id();
        return $data;
    }
}
