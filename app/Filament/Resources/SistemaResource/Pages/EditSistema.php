<?php

// app/Filament/Resources/SistemaResource/Pages/EditSistema.php

namespace App\Filament\Resources\SistemaResource\Pages;

use App\Filament\Resources\SistemaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSistema extends EditRecord
{
    protected static string $resource = SistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalDescription('Atenció: Aquesta acció eliminarà permanentment el sistema i totes les seves dades relacionades (nivells d\'accés, sol·licituds, etc.).'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Carregar configuració de validadors per edició - USAR EL NOU ACCESSOR
        if (isset($data['configuracio_validadors'])) {
            $validadors = json_decode($data['configuracio_validadors'], true);
            if (is_array($validadors)) {
                $data['configuracio_validadors_temp'] = $this->transformarValidadorsPerEdicio($validadors);
            }
        }
        
        // Carregar departaments autoritzats
        $data['departaments_autoritzats'] = $this->getRecord()->departaments()->pluck('departaments.id')->toArray();
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Processar configuració de validadors - USAR json_encode MANUALMENT
        if (isset($data['configuracio_validadors_temp'])) {
            $processedValidadors = $this->processarConfigValidadors($data['configuracio_validadors_temp']);
            $data['configuracio_validadors'] = json_encode($processedValidadors);
            unset($data['configuracio_validadors_temp']);
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Sincronitzar departaments autoritzats
        if (isset($this->data['departaments_autoritzats'])) {
            $this->getRecord()->departaments()->sync($this->data['departaments_autoritzats']);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Sistema actualitzat correctament';
    }

    private function transformarValidadorsPerEdicio(array $validadors): array
    {
        $transformed = [];
        
        foreach ($validadors as $validador) {
            $item = [
                'tipus_validador' => $validador['tipus'],
                'ordre' => $validador['ordre'] ?? 1,
                'obligatori' => $validador['obligatori'] ?? true
            ];
            
            if (isset($validador['usuari_id'])) {
                $item['usuari_id'] = $validador['usuari_id'];
            }
            
            if (isset($validador['rol'])) {
                $item['rol'] = $validador['rol'];
            }
            
            $transformed[] = $item;
        }
        
        return $transformed;
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