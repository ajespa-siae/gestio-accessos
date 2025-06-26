<?php

namespace App\Filament\Resources\EmpleatResource\Pages;

use App\Filament\Resources\EmpleatResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\Empleat;

class CreateEmpleat extends CreateRecord
{
    protected static string $resource = EmpleatResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Afegir dades automàtiques
        $data['usuari_creador_id'] = auth()->id();
        $data['identificador_unic'] = $this->generarIdentificadorUnic();
        $data['data_alta'] = now();
        $data['estat'] = 'actiu';
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        $empleat = $this->getRecord();
        
        // Disparar Job d'onboarding automàtic
        try {
            \App\Jobs\CrearChecklistOnboarding::dispatch($empleat);
            
            Notification::make()
                ->title('Empleat creat correctament')
                ->body("S'ha creat l'empleat {$empleat->nom_complet} i s'ha iniciat el procés d'onboarding automàticament.")
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('veure_checklist')
                        ->label('Veure Checklist')
                        ->url($this->getResource()::getUrl('view', ['record' => $empleat])),
                ])
                ->persistent()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Empleat creat amb avís')
                ->body("L'empleat s'ha creat correctament però hi ha hagut un problema creant la checklist d'onboarding: " . $e->getMessage())
                ->warning()
                ->persistent()
                ->send();
        }
        
        // Log de l'acció per auditoria
        activity()
            ->performedOn($empleat)
            ->withProperties([
                'identificador_unic' => $empleat->identificador_unic,
                'departament' => $empleat->departament->nom,
                'usuari_creador' => auth()->user()->name
            ])
            ->log('Empleat creat amb onboarding automàtic');
    }
    
    private function generarIdentificadorUnic(): string
    {
        $prefix = 'EMP';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 8));
        
        $identificador = "{$prefix}-{$timestamp}-{$random}";
        
        // Verificar que sigui únic
        while (Empleat::where('identificador_unic', $identificador)->exists()) {
            $random = strtoupper(substr(md5(uniqid()), 0, 8));
            $identificador = "{$prefix}-{$timestamp}-{$random}";
        }
        
        return $identificador;
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // Desactivar notificació per defecte perquè usem la personalitzada
    }
}