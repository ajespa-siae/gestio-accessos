<?php

namespace App\Console\Commands;

use App\Models\ChecklistTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActualizarTareasChecklist extends Command
{
    protected $signature = 'app:actualizar-tareas-checklist {checklist_id?}';
    protected $description = 'Actualiza las tareas de checklist para usar roles en lugar de usuarios específicos';

    public function handle()
    {
        $checklistId = $this->argument('checklist_id');
        
        $this->info("Actualizando tareas de checklist para usar roles en lugar de usuarios específicos");
        
        try {
            DB::beginTransaction();
            
            $query = ChecklistTask::query();
            
            if ($checklistId) {
                $query->where('checklist_instance_id', $checklistId);
            }
            
            // Solo actualizamos las tareas pendientes (no completadas)
            $tareas = $query->where('completada', false)->get();
            
            $this->info("Se encontraron {$tareas->count()} tareas pendientes para actualizar");
            
            foreach ($tareas as $tarea) {
                // Guardamos el rol_assignat original
                $rolAsignado = $tarea->rol_assignat;
                
                // Actualizamos la tarea para eliminar el usuario específico
                $tarea->update([
                    'usuari_assignat_id' => null,
                    // Nos aseguramos de que el rol_assignat esté establecido
                    'rol_assignat' => $rolAsignado ?: ($tarea->usuariAssignat ? $tarea->usuariAssignat->rol_principal : null)
                ]);
                
                $this->info("Tarea ID {$tarea->id} actualizada: {$tarea->nom} - Rol: {$tarea->rol_assignat}");
            }
            
            DB::commit();
            
            $this->info("Actualización completada con éxito");
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al actualizar tareas: " . $e->getMessage());
            return 1;
        }
    }
}
