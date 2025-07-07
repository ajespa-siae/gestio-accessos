<?php

namespace App\Filament\Operatiu\Resources\ValidacioResource\Pages;

use App\Filament\Operatiu\Resources\ValidacioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditValidacio extends EditRecord
{
    protected static string $resource = ValidacioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Veure')
                ->color('gray'),
                
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->visible(fn (): bool => 
                    $this->record->estat === 'pendent' && 
                    auth()->user()->hasRole('admin')
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si es una validación individual, aseguramos que solo haya un validador
        if (($data['tipus_validacio'] ?? null) === 'individual') {
            $data['grup_validadors_ids'] = null;
        }
        
        return $data;
    }
}
