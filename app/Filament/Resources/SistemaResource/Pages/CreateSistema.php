<?php

namespace App\Filament\Resources\SistemaResource\Pages;

use App\Filament\Resources\SistemaResource;
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
        // Extraure dades temporals per processar després de crear el sistema
        $this->nivellsTemporals = $data['nivells_temporals'] ?? [];
        $this->validadorsTemporals = $data['validadors_temporals'] ?? [];
        $this->departamentsTemporals = $data['departaments_temporals'] ?? [];
        
        // Eliminar camps temporals del sistema principal
        unset($data['nivells_temporals'], $data['validadors_temporals'], $data['departaments_temporals']);
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        $sistema = $this->getRecord();
        
        // Crear nivells d'accés
        $this->crearNivellsAcces($sistema);
        
        // Crear validadors mixt
        $this->crearValidadors($sistema);
        
        // Assignar departaments
        $this->assignarDepartaments($sistema);
        
        // Notificació de creació exitosa
        Notification::make()
            ->title('Sistema creat correctament')
            ->body($this->generarResumCreacio($sistema))
            ->success()
            ->persistent()
            ->send();
    }
    
    private function crearNivellsAcces($sistema): void
    {
        if (empty($this->nivellsTemporals)) {
            return;
        }
        
        foreach ($this->nivellsTemporals as $nivellData) {
            $sistema->nivellsAcces()->create([
                'nom' => $nivellData['nom'],
                'descripcio' => $nivellData['descripcio'] ?? null,
                'ordre' => $nivellData['ordre'] ?? 1,
                'actiu' => $nivellData['actiu'] ?? true,
            ]);
        }
    }
    
    private function crearValidadors($sistema): void
    {
        if (empty($this->validadorsTemporals)) {
            return;
        }
        
        foreach ($this->validadorsTemporals as $validadorData) {
            $sistema->sistemaValidadors()->create([
                'validador_id' => $validadorData['validador_id'] ?? null,
                'tipus_validador' => $validadorData['tipus_validador'],
                'ordre' => $validadorData['ordre'] ?? 1,
                'requerit' => $validadorData['requerit'] ?? true,
                'actiu' => $validadorData['actiu'] ?? true,
            ]);
        }
    }
    
    private function assignarDepartaments($sistema): void
    {
        if (empty($this->departamentsTemporals)) {
            return;
        }
        
        $assignacions = [];
        foreach ($this->departamentsTemporals as $departamentId) {
            $assignacions[$departamentId] = ['acces_per_defecte' => false];
        }
        
        $sistema->departaments()->attach($assignacions);
    }
    
    private function generarResumCreacio($sistema): string
    {
        $resum = "Sistema '{$sistema->nom}' creat amb:\n";
        
        $nivells = count($this->nivellsTemporals);
        $validadors = count($this->validadorsTemporals);
        $departaments = count($this->departamentsTemporals);
        
        if ($nivells > 0) $resum .= "• {$nivells} nivells d'accés\n";
        if ($validadors > 0) $resum .= "• {$validadors} validadors configurats\n";
        if ($departaments > 0) $resum .= "• Assignat a {$departaments} departaments\n";
        
        if ($nivells === 0 && $validadors === 0 && $departaments === 0) {
            $resum .= "\n⚠️ Recorda configurar nivells, validadors i departaments des de les pestanyes corresponents.";
        }
        
        return $resum;
    }
    
    // Propietats per emmagatzemar dades temporals
    private array $nivellsTemporals = [];
    private array $validadorsTemporals = [];
    private array $departamentsTemporals = [];
}