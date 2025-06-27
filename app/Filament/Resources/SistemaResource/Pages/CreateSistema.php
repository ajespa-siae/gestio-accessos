<?php

namespace App\Filament\Resources\SistemaResource\Pages;

use App\Filament\Resources\SistemaResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\Sistema;

class CreateSistema extends CreateRecord
{
    protected static string $resource = SistemaResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
    
    protected function afterCreate(): void
    {
        $sistema = $this->getRecord();
        $data = $this->form->getRawState();
        
        $createdItems = [
            'nivells' => 0,
            'validadors' => 0,
            'departaments' => 0
        ];
        
        // Crear nivells d'accés si s'han definit
        if (isset($data['nivells_temporals']) && is_array($data['nivells_temporals'])) {
            foreach ($data['nivells_temporals'] as $nivellData) {
                if (!empty($nivellData['nom'])) {
                    $sistema->nivellsAcces()->create([
                        'nom' => $nivellData['nom'],
                        'descripcio' => $nivellData['descripcio'] ?? null,
                        'ordre' => $nivellData['ordre'] ?? 1,
                        'actiu' => $nivellData['actiu'] ?? true,
                    ]);
                    $createdItems['nivells']++;
                }
            }
        }
        
        // Crear validadors si s'han definit
        if (isset($data['validadors_temporals']) && is_array($data['validadors_temporals'])) {
            foreach ($data['validadors_temporals'] as $validadorData) {
                if (!empty($validadorData['validador_id'])) {
                    try {
                        $sistema->validadors()->attach($validadorData['validador_id'], [
                            'ordre' => $validadorData['ordre'] ?? 1,
                            'requerit' => $validadorData['requerit'] ?? true,
                            'actiu' => $validadorData['actiu'] ?? true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $createdItems['validadors']++;
                    } catch (\Exception $e) {
                        // Log l'error però continua amb la creació
                        \Log::warning('Error creating validator for sistema: ' . $e->getMessage());
                    }
                }
            }
        }
        
        // Assignar departaments si s'han seleccionat
        if (isset($data['departaments_temporals']) && is_array($data['departaments_temporals'])) {
            foreach ($data['departaments_temporals'] as $departamentId) {
                $sistema->departaments()->attach($departamentId, [
                    'acces_per_defecte' => false,
                ]);
                $createdItems['departaments']++;
            }
        }
        
        // Crear notificació amb resum
        $this->crearNotificacioCreacio($sistema, $createdItems);
    }
    
    private function crearNotificacioCreacio(Sistema $sistema, array $items): void
    {
        $resumen = [];
        
        if ($items['nivells'] > 0) {
            $resumen[] = "{$items['nivells']} nivell" . ($items['nivells'] > 1 ? 's' : '') . " d'accés";
        }
        
        if ($items['validadors'] > 0) {
            $resumen[] = "{$items['validadors']} validador" . ($items['validadors'] > 1 ? 's' : '');
        }
        
        if ($items['departaments'] > 0) {
            $resumen[] = "{$items['departaments']} departament" . ($items['departaments'] > 1 ? 's' : '');
        }
        
        $bodyText = "Sistema '{$sistema->nom}' creat correctament.";
        if (!empty($resumen)) {
            $bodyText .= " S'han configurat: " . implode(', ', $resumen) . ".";
        }
        
        $actions = [
            \Filament\Notifications\Actions\Action::make('configurar')
                ->label('Configurar Més')
                ->url($this->getResource()::getUrl('edit', ['record' => $sistema])),
        ];
        
        // Afegir avisos si falten configuracions
        $warnings = [];
        if ($items['nivells'] === 0) {
            $warnings[] = 'nivells d\'accés';
        }
        if ($items['validadors'] === 0) {
            $warnings[] = 'validadors';
        }
        if ($items['departaments'] === 0) {
            $warnings[] = 'departaments';
        }
        
        if (!empty($warnings)) {
            $actions[] = \Filament\Notifications\Actions\Action::make('completar')
                ->label('Completar Configuració')
                ->url($this->getResource()::getUrl('edit', ['record' => $sistema]));
        }
        
        Notification::make()
            ->title('Sistema creat correctament')
            ->body($bodyText)
            ->success()
            ->actions($actions)
            ->persistent()
            ->send();
            
        // Notificació d'avís si hi ha configuracions pendents
        if (!empty($warnings)) {
            Notification::make()
                ->title('Configuració pendent')
                ->body('El sistema necessita configurar: ' . implode(', ', $warnings) . '.')
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('configurar_ara')
                        ->label('Configurar Ara')
                        ->url($this->getResource()::getUrl('edit', ['record' => $sistema])),
                ])
                ->send();
        }
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // Desactivar notificació per defecte
    }
}