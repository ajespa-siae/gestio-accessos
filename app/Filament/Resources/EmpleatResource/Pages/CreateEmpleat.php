<?php

namespace App\Filament\Resources\EmpleatResource\Pages;

use App\Filament\Resources\EmpleatResource;
use App\Models\Empleat;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateEmpleat extends CreateRecord
{
    protected static string $resource = EmpleatResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Assignar l'usuari creador
        $data['usuari_creador_id'] = Auth::id();
        
        // Assegurar que l'estat és actiu per nous empleats
        $data['estat'] = 'actiu';
        
        // Assegurar data d'alta
        $data['data_alta'] = $data['data_alta'] ?? now();
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $empleat = $this->getRecord();
        
        Notification::make()
            ->title('Empleat creat correctament')
            ->body("S'ha creat l'empleat {$empleat->nom_complet} i s'han iniciat els processos d'onboarding automàticament.")
            ->success()
            ->duration(5000)
            ->send();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // Desactivem la notificació per defecte
    }
}