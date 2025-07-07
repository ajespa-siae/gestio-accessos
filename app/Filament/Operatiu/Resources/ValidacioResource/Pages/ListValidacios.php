<?php

namespace App\Filament\Operatiu\Resources\ValidacioResource\Pages;

use App\Filament\Operatiu\Resources\ValidacioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValidacios extends ListRecords
{
    protected static string $resource = ValidacioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Validació')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => auth()->user()->hasRole('admin')),
        ];
    }
}
