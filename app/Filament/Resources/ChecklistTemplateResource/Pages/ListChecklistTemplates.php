<?php

namespace App\Filament\Resources\ChecklistTemplateResource\Pages;

use App\Filament\Resources\ChecklistTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListChecklistTemplates extends ListRecords
{
    protected static string $resource = ChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Plantilla'),
        ];
    }
    
    protected function crearPlantillesPerDefecte(): void
    {
        $plantilles = [
            [
                'nom' => 'Onboarding IT Estàndard',
                'tipus' => 'onboarding',
                'departament_id' => null,
                'tasques' => [
                    ['nom' => 'Crear usuari LDAP', 'ordre' => 1, 'rol_assignat' => 'it', 'dies_limit' => 1],
                    ['nom' => 'Crear compte de correu', 'ordre' => 2, 'rol_assignat' => 'it', 'dies_limit' => 1],
                    ['nom' => 'Assignar grups LDAP', 'ordre' => 3, 'rol_assignat' => 'it', 'dies_limit' => 2],
                    ['nom' => 'Preparar equip informàtic', 'ordre' => 4, 'rol_assignat' => 'it', 'dies_limit' => 3],
                    ['nom' => 'Configurar perfil d\'usuari', 'ordre' => 5, 'rol_assignat' => 'it', 'dies_limit' => 2],
                    ['nom' => 'Notificar finalització RRHH', 'ordre' => 6, 'rol_assignat' => 'it', 'dies_limit' => 1],
                ]
            ],
            [
                'nom' => 'Offboarding IT Estàndard',
                'tipus' => 'offboarding',
                'departament_id' => null,
                'tasques' => [
                    ['nom' => 'Deshabilitar usuari LDAP', 'ordre' => 1, 'rol_assignat' => 'it', 'dies_limit' => 1],
                    ['nom' => 'Revocar accessos sistemes', 'ordre' => 2, 'rol_assignat' => 'it', 'dies_limit' => 1],
                    ['nom' => 'Backup dades personal', 'ordre' => 3, 'rol_assignat' => 'it', 'dies_limit' => 3],
                    ['nom' => 'Recuperar equip informàtic', 'ordre' => 4, 'rol_assignat' => 'it', 'dies_limit' => 5],
                    ['nom' => 'Eliminar compte correu', 'ordre' => 5, 'rol_assignat' => 'it', 'dies_limit' => 7],
                    ['nom' => 'Documentar finalització', 'ordre' => 6, 'rol_assignat' => 'it', 'dies_limit' => 1],
                ]
            ]
        ];
        
        foreach ($plantilles as $plantillaData) {
            $plantilla = \App\Models\ChecklistTemplate::create([
                'nom' => $plantillaData['nom'],
                'tipus' => $plantillaData['tipus'],
                'departament_id' => $plantillaData['departament_id'],
                'actiu' => true
            ]);
            
            foreach ($plantillaData['tasques'] as $tascaData) {
                $plantilla->tasquesTemplate()->create([
                    'nom' => $tascaData['nom'],
                    'ordre' => $tascaData['ordre'],
                    'rol_assignat' => $tascaData['rol_assignat'],
                    'dies_limit' => $tascaData['dies_limit'],
                    'obligatoria' => true,
                    'activa' => true
                ]);
            }
        }
        
        \Filament\Notifications\Notification::make()
            ->title('Plantilles creades correctament')
            ->success()
            ->send();
    }
}