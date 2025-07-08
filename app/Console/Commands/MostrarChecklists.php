<?php

namespace App\Console\Commands;

use App\Models\ChecklistInstance;
use App\Models\Empleat;
use Illuminate\Console\Command;

class MostrarChecklists extends Command
{
    protected $signature = 'app:mostrar-checklists {empleat_id?}';
    protected $description = 'Muestra las checklists existentes en el sistema';

    public function handle()
    {
        $empleatId = $this->argument('empleat_id');
        
        if ($empleatId) {
            $empleat = Empleat::find($empleatId);
            if (!$empleat) {
                $this->error("No se encontró el empleado con ID {$empleatId}");
                return 1;
            }
            
            $checklists = ChecklistInstance::where('empleat_id', $empleatId)->get();
            $this->info("Checklists para el empleado {$empleat->nom_complet} (ID: {$empleatId}):");
        } else {
            $checklists = ChecklistInstance::all();
            $this->info("Todas las checklists en el sistema:");
        }
        
        if ($checklists->isEmpty()) {
            $this->warn("No se encontraron checklists.");
            return 0;
        }
        
        $headers = ['ID', 'Empleado ID', 'Nombre Empleado', 'Tipo', 'Estado', 'Creado'];
        $rows = [];
        
        foreach ($checklists as $checklist) {
            $empleat = $checklist->empleat;
            $nombreEmpleado = $empleat ? $empleat->nom_complet : 'N/A';
            $tipo = $checklist->template ? $checklist->template->tipus : 'N/A';
            
            $rows[] = [
                $checklist->id,
                $checklist->empleat_id,
                $nombreEmpleado,
                $tipo,
                $checklist->estat,
                $checklist->created_at->format('Y-m-d H:i:s')
            ];
        }
        
        $this->table($headers, $rows);
        return 0;
    }
}
