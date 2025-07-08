<?php

namespace App\Console\Commands;

use App\Models\Empleat;
use App\Models\ChecklistTemplate;
use Illuminate\Console\Command;

class VerificarEmpleado extends Command
{
    protected $signature = 'app:verificar-empleado {empleat_id}';
    protected $description = 'Verifica los datos de un empleado y su capacidad para crear checklists';

    public function handle()
    {
        $empleatId = $this->argument('empleat_id');
        $empleat = Empleat::find($empleatId);

        if (!$empleat) {
            $this->error("No se encontró el empleado con ID {$empleatId}");
            return 1;
        }

        $this->info("=== Información del Empleado ===");
        $this->info("ID: {$empleat->id}");
        $this->info("Nombre: {$empleat->nom_complet}");
        $this->info("Identificador único: {$empleat->identificador_unic}");
        $this->info("Departamento ID: " . ($empleat->departament_id ?? 'null'));
        $this->info("Departamento: " . ($empleat->departament ? $empleat->departament->nom : 'No asignado'));
        $this->info("Usuario creador ID: " . ($empleat->usuari_creador_id ?? 'null'));
        
        // Verificar si hay plantillas de onboarding disponibles
        $this->info("\n=== Plantillas de Onboarding Disponibles ===");
        
        // Plantillas específicas del departamento
        if ($empleat->departament_id) {
            $plantillasDepartamento = ChecklistTemplate::where('tipus', 'onboarding')
                ->where('actiu', true)
                ->where('departament_id', $empleat->departament_id)
                ->get();
                
            $this->info("Plantillas específicas del departamento: " . $plantillasDepartamento->count());
            foreach ($plantillasDepartamento as $plantilla) {
                $this->info("- ID: {$plantilla->id}, Nombre: {$plantilla->nom}");
            }
        } else {
            $this->warn("El empleado no tiene departamento asignado, no se pueden buscar plantillas específicas");
        }
        
        // Plantillas globales
        $plantillasGlobales = ChecklistTemplate::where('tipus', 'onboarding')
            ->where('actiu', true)
            ->whereNull('departament_id')
            ->get();
            
        $this->info("Plantillas globales: " . $plantillasGlobales->count());
        foreach ($plantillasGlobales as $plantilla) {
            $this->info("- ID: {$plantilla->id}, Nombre: {$plantilla->nom}");
        }
        
        // Verificar si se puede crear una plantilla básica
        $this->info("\n=== Simulación de Creación de Plantilla Básica ===");
        try {
            // Simular la creación de una plantilla básica sin guardarla
            $this->info("Departamento ID para la plantilla: " . ($empleat->departament_id ?? 'null'));
            
            if ($empleat->departament_id === null) {
                $this->error("No se puede crear una plantilla básica porque el empleado no tiene departamento asignado");
            } else {
                $this->info("La creación de una plantilla básica debería ser posible");
            }
        } catch (\Exception $e) {
            $this->error("Error al simular la creación de plantilla básica: " . $e->getMessage());
        }
        
        return 0;
    }
}
