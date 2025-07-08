<?php

namespace App\Console\Commands;

use App\Jobs\NotificarNovaChecklistIT;
use App\Models\ChecklistInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarNotificacionEmail extends Command
{
    protected $signature = 'app:enviar-notificacion-email {checklist_id}';
    protected $description = 'Envía notificaciones por email para una checklist específica';

    public function handle()
    {
        $checklistId = $this->argument('checklist_id');
        
        $checklist = ChecklistInstance::find($checklistId);
        
        if (!$checklist) {
            $this->error("No se encontró la checklist con ID: {$checklistId}");
            return 1;
        }
        
        $empleat = $checklist->empleat;
        
        $this->info("Enviando notificaciones por email para la checklist {$checklistId} del empleado {$empleat->nom_complet}");
        
        try {
            NotificarNovaChecklistIT::dispatch($checklist);
            $this->info("Notificaciones enviadas correctamente");
            return 0;
        } catch (\Exception $e) {
            $this->error("Error al enviar notificaciones: " . $e->getMessage());
            Log::error("Error al enviar notificaciones para checklist {$checklistId}: " . $e->getMessage());
            return 1;
        }
    }
}
