<?php

// app/Filament/Resources/SistemaResource/Pages/CreateSistema.php

namespace App\Filament\Resources\SistemaResource\Pages;

use App\Filament\Resources\SistemaResource;
use App\Models\Sistema;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateSistema extends CreateRecord
{
    protected static string $resource = SistemaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Processar configuració de validadors - USAR EL NOU MUTATOR
        if (isset($data['configuracio_validadors_temp'])) {
            $processedValidadors = $this->processarConfigValidadors($data['configuracio_validadors_temp']);
            // USAR json_encode MANUALMENT
            $data['configuracio_validadors'] = json_encode($processedValidadors);
            unset($data['configuracio_validadors_temp']);
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $sistema = $this->getRecord();
        
        // Sincronitzar departaments autoritzats
        if (isset($this->data['departaments_autoritzats'])) {
            $sistema->departaments()->sync($this->data['departaments_autoritzats']);
        }
        
        Notification::make()
            ->title('Sistema creat correctament')
            ->body("El sistema '{$sistema->nom}' s'ha creat. Ara podeu configurar els nivells d'accés.")
            ->success()
            ->duration(5000)
            ->send();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // Desactivem la notificació per defecte
    }

    private function processarConfigValidadors(array $validadors): array
    {
        $processedValidadors = [];
        
        foreach ($validadors as $validador) {
            $config = [
                'tipus' => $validador['tipus_validador'],
                'ordre' => $validador['ordre'] ?? 1,
                'obligatori' => $validador['obligatori'] ?? true
            ];
            
            switch ($validador['tipus_validador']) {
                case 'usuari_especific':
                    $config['usuari_id'] = $validador['usuari_id'];
                    break;
                case 'rol':
                    $config['rol'] = $validador['rol'];
                    break;
            }
            
            $processedValidadors[] = $config;
        }
        
        // Ordenar per ordre
        usort($processedValidadors, fn($a, $b) => $a['ordre'] <=> $b['ordre']);
        
        return $processedValidadors;
    }
}