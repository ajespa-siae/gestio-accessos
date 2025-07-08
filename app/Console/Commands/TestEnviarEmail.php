<?php

namespace App\Console\Commands;

use App\Mail\NovaChecklistMail;
use App\Models\ChecklistInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestEnviarEmail extends Command
{
    protected $signature = 'app:test-email {checklist_id} {email?}';
    protected $description = 'Prueba el envío de emails para una checklist específica';

    public function handle()
    {
        $checklistId = $this->argument('checklist_id');
        $email = $this->argument('email') ?? 'test@example.com';
        
        $checklist = ChecklistInstance::find($checklistId);
        
        if (!$checklist) {
            $this->error("No se encontró la checklist con ID: {$checklistId}");
            return 1;
        }
        
        $empleat = $checklist->empleat;
        
        $this->info("Enviando email de prueba para la checklist {$checklistId} del empleado {$empleat->nom_complet} a {$email}");
        
        try {
            // Usar la configuración de correo del .env
            // No forzamos ningún driver para respetar la configuración actual
            
            // Enviar el email
            Mail::to($email)->send(new NovaChecklistMail($checklist));
            
            $this->info("Email enviado correctamente. Revisa los logs en storage/logs/laravel.log");
            
            // Mostrar el contenido del email en la consola
            $this->info("\n--- Contenido del email (simulado) ---\n");
            $this->info("Asunto: Nueva checklist {$checklist->getTipusTemplate()} - {$empleat->nom_complet}");
            $this->info("Para: {$email}");
            $this->info("Contenido: Se ha creado una nueva checklist para el empleado {$empleat->nom_complet}");
            $this->info("URL de la checklist: " . url("/admin/checklist-instances/{$checklist->id}"));
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error al enviar email: " . $e->getMessage());
            Log::error("Error al enviar email de prueba para checklist {$checklistId}: " . $e->getMessage());
            return 1;
        }
    }
}
