<?php

// app/Filament/Resources/DepartamentResource/Pages/CreateDepartament.php (Actualitzat)

namespace App\Filament\Resources\DepartamentResource\Pages;

use App\Filament\Resources\DepartamentResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use App\Models\Sistema;

class CreateDepartament extends CreateRecord
{
    protected static string $resource = DepartamentResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extraure dades temporals
        $this->gestorsTemporals = $data['gestors_temporals'] ?? [];
        $this->sistemesSeleccionats = $data['sistemes_seleccionats'] ?? [];
        
        // Netejar dades temporals del departament principal
        unset($data['gestors_temporals'], $data['sistemes_seleccionats']);
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        $departament = $this->getRecord();
        
        // Assignar gestors múltiples
        $this->assignarGestors($departament);
        
        // Assignar sistemes
        $this->assignarSistemes($departament);
        
        // Notificació de creació
        \Filament\Notifications\Notification::make()
            ->title('Departament creat correctament')
            ->body($this->generarResumCreacio($departament))
            ->success()
            ->send();
    }
    
    private function assignarGestors($departament): void
    {
        if (empty($this->gestorsTemporals)) {
            return;
        }
        
        $primerGestor = true;
        foreach ($this->gestorsTemporals as $gestorId) {
            $gestor = User::find($gestorId);
            if ($gestor) {
                $departament->afegirGestor($gestor, $primerGestor);
                $primerGestor = false; // Només el primer és principal
            }
        }
    }
    
    private function assignarSistemes($departament): void
    {
        if (empty($this->sistemesSeleccionats)) {
            return;
        }
        
        $assignacions = [];
        foreach ($this->sistemesSeleccionats as $sistemaId) {
            $assignacions[$sistemaId] = ['acces_per_defecte' => false];
        }
        
        $departament->sistemes()->attach($assignacions);
    }
    
    private function generarResumCreacio($departament): string
    {
        $resum = "Departament '{$departament->nom}' creat amb:\n";
        
        $gestors = count($this->gestorsTemporals);
        $sistemes = count($this->sistemesSeleccionats);
        
        if ($gestors > 0) {
            $resum .= "• {$gestors} gestor(s) assignat(s)\n";
        }
        if ($sistemes > 0) {
            $resum .= "• {$sistemes} sistemes assignats\n";
        }
        
        if ($gestors === 0) {
            $resum .= "\n⚠️ Recorda assignar gestors des de la pestanya 'Gestors'";
        }
        
        return $resum;
    }
    
    // Propietats per emmagatzemar dades temporals
    private array $gestorsTemporals = [];
    private array $sistemesSeleccionats = [];
}