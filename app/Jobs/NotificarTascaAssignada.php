<?php

namespace App\Jobs;

use App\Models\ChecklistTask;
use App\Models\Notificacio;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarTascaAssignada implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ChecklistTask $task
    ) {}

    public function handle(): void
    {
        try {
            $usuariAssignat = $this->task->usuariAssignat;
            
            if (!$usuariAssignat) {
                Log::info("No se puede notificar: La tarea {$this->task->nom} no tiene usuario asignado");
                return;
            }
            
            $checklistInstance = $this->task->checklistInstance;
            $empleat = $checklistInstance ? $checklistInstance->empleat : null;
            $tipus = $checklistInstance ? $checklistInstance->getTipusTemplate() : 'general';
            
            // Crear notificación interna para el usuario asignado
            Notificacio::crear(
                $usuariAssignat->id,
                "Nova tasca assignada",
                "Tasca: {$this->task->nom}" . 
                ($empleat ? "\nEmpleat/da: {$empleat->nom_complet}" : "") . 
                ($this->task->data_limit ? "\nData límit: " . $this->task->data_limit->format('d/m/Y') : ""),
                'info',
                "/operatiu/checklist-tasks/{$this->task->id}",
                $empleat ? $empleat->identificador_unic : null
            );
            
            // Si el usuario asignado tiene rol IT, enviar también correo electrónico en catalán
            if ($usuariAssignat->hasRole('it') || $usuariAssignat->rol_principal === 'it') {
                $this->enviarEmailIT($usuariAssignat);
            }
            
            Log::info("Notificación enviada por tarea asignada: {$this->task->nom} a {$usuariAssignat->name}");
            
        } catch (\Exception $e) {
            Log::error("Error notificando tarea asignada: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Envía un correo electrónico en catalán al usuario IT
     */
    private function enviarEmailIT(User $usuari): void
    {
        try {
            $task = $this->task;
            $checklistInstance = $task->checklistInstance;
            $empleat = $checklistInstance ? $checklistInstance->empleat : null;
            
            $data = [
                'task' => $task,
                'empleat' => $empleat,
                'usuari' => $usuari,
            ];
            
            Mail::send('emails.tasca-assignada', $data, function ($message) use ($usuari, $task) {
                $message->to($usuari->email)
                    ->subject("[SIAE] Nova tasca assignada: {$task->nom}")
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            Log::info("Correo enviado a {$usuari->email} por tarea asignada: {$task->nom}");
            
        } catch (\Exception $e) {
            Log::error("Error enviando correo de tarea asignada: " . $e->getMessage());
        }
    }
}
