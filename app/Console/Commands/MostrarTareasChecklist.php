<?php

namespace App\Console\Commands;

use App\Models\ChecklistInstance;
use App\Models\ChecklistTasca;
use Illuminate\Console\Command;

class MostrarTareasChecklist extends Command
{
    protected $signature = 'app:mostrar-tareas-checklist {checklist_id}';
    protected $description = 'Muestra las tareas de una checklist específica';

    public function handle()
    {
        $checklistId = $this->argument('checklist_id');
        $checklist = ChecklistInstance::find($checklistId);

        if (!$checklist) {
            $this->error("No se encontró la checklist con ID {$checklistId}");
            return 1;
        }

        $this->info("Tareas de la checklist ID: {$checklist->id} para {$checklist->empleat->nom_complet}");
        
        $tareas = $checklist->tasques()->orderBy('ordre')->get();
        
        if ($tareas->isEmpty()) {
            $this->warn("No hay tareas en esta checklist");
            return 0;
        }
        
        $headers = ['ID', 'Nombre', 'Rol Asignado', 'Usuario Asignado', 'Estado'];
        $rows = [];
        
        foreach ($tareas as $tarea) {
            $usuarioAsignado = $tarea->usuariAssignat ? 
                "{$tarea->usuariAssignat->name} (ID: {$tarea->usuariAssignat->id})" : 
                'No asignado';
                
            $rows[] = [
                $tarea->id,
                $tarea->nom,
                $tarea->rol_assignat ?? 'No definido',
                $usuarioAsignado,
                $tarea->completada ? 'Completada' : 'Pendiente'
            ];
        }
        
        $this->table($headers, $rows);
        
        return 0;
    }
}
